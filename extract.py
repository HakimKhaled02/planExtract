import sys
import json
import re
import warnings
import unicodedata
from PIL import Image
import pytesseract

warnings.filterwarnings("ignore")

# =============================================================================
# GENERIC OCR EXTRACTION ENGINE
# No column is hardcoded — all fields are driven by header pattern lists.
# To add/rename a column, update HEADER_FIELDS or TABLE_COLUMNS below.
# =============================================================================

# ---------------------------------------------------------------------------
# Known column header patterns — used BOTH to locate each column AND as
# stop-signals when extracting a neighbouring column.
# Each entry: (field_key, [regex variants for the header])
# ---------------------------------------------------------------------------
TABLE_COLUMNS = [
    ("no_pendaftaran_loji",  [r"No\.?\s*P[oe]ndaftaran\s+Loji", r"No\.\s*Pendaftaran\s+Loji"]),
    ("kategori_caj",         [r"Kat[eo]gori\s+Caj", r"Kategori\s*Caj\s*[-–]\s*Jenis"]),
    ("sub_jenis_loji",       [r"Sub\s+J[oe]nis\s*(?:Loji)?", r"Sub\s+Jenis"]),
    ("kod_loji",             [r"Kod\s+Loji", r"Status\s+Loji"]),
    ("no_siri_loji",         [r"No\.?\s*Siri\s*(?:Loji)?", r"No\.?\s*Siri"]),
    ("tarikh_luput",         [r"Tarikh\s+(?:Luput|Tamat|CF)", r"Luput\s+CF", r"Expiry"]),
    ("kadar_caj",            [r"Kadar\s*(?:Caj)?(?:\s*\(.*?\))?\s*", r"Kadar\s*\(RM", r"Rate\s*(?:\(.*?\))?\s*", r"Kadar\b"]),
    ("semakan",              [r"Semakan", r"Inspection"]),
    ("no_pendaftaran_penghuni", [r"No\.?\s*Pendaftaran\s*\n?\s*Penghuni", r"Penghuni"]),
]

HEADER_FIELDS = [
    ("nama_pemunya",         [r"Nama\s+Pemunya"]),
    ("no_pendaftaran_pemunya", [r"No\.?\s*(?:Nombor\s+)?P[eе]ndafta\w*\s+P[eе]mun(?:ya|yv|mys|wny)"]),
    ("alamat_kedudukan",     [r"Alamat\s+Kedudukan"]),
    ("pegawai_dihubungi",    [r"Pegawai\s+Dihubungi", r"Pegawai\s+Yang\s+Dihubungi"]),
    ("no_telefon",           [r"No\.?\s*(?:,\s*)?Telefon", r"Tel\.?\s*:"]),
    ("poskod",               [r"Poskod"]),
    ("bandar",               [r"Bandar\b"]),
    ("negeri",               [r"Negeri\b"]),
]

# Combined stop regex — any known header acts as a column boundary
def _build_stop_re():
    all_pats = []
    for _, variants in TABLE_COLUMNS + HEADER_FIELDS:
        all_pats.extend(variants)
    combined = "|".join(f"(?:{p})" for p in all_pats)
    return re.compile(r"(?i)^\s*(?:" + combined + r")\s*$", re.MULTILINE)

_STOP_HEADER_RE = _build_stop_re()

# Also a looser inline stop check
_STOP_INLINE_RE = re.compile(
    r"(?i)\b(SL-?PMA|Lihat\s+Loj|DISAHKAN|Semakan\b|Tarikh\s+Luput)\b"
)

# Lines that are definitely noise regardless of column
_GLOBAL_NOISE_RE = re.compile(
    r"(?i)^\s*("
    r"Senarai\s+Loji|Maklumat\s+Loji|Maklumat\s+Hubungan"
    r"|No\.?\s*Faks|Emel\b|Longitud|Latitud|Lintang|Longitude|Latitude"
    r"|Garis\s+Bujur|Garis\s+Lintang"
    r"|Pilihan\s+IKKP"
    r")\b"
)

# Pure row-number lines — only skip 1–2 digit standalone numbers.
# 3-digit numbers (e.g. 150, 750) could be valid rate/serial values.
_ROW_NUM_RE = re.compile(r"^\d{1,2}$")

# GPS coordinate values
_GPS_RE = re.compile(r"^-?\d{1,3}\.\d{4,}$")


# ---------------------------------------------------------------------------
# Generic value cleaner — works for any column
# ---------------------------------------------------------------------------
def _clean_value(s: str) -> str:
    """Normalise and strip noise from a single OCR cell value."""
    s = unicodedata.normalize("NFKC", s).strip()
    # Strip smart quotes / decorative chars OCR inserts
    s = s.strip("\"'`\u00ab\u00bb\u201a\u2018\u2019\u201c\u201d\u201e")
    s = re.sub(r"\s+", " ", s).strip()

    if not s or len(s) < 1 or len(s) > 250:
        return ""

    # Keep numeric / money values (e.g. "150", "1,500.00", "RM 75") — never filter these
    if re.match(r"^(?:RM\s*)?[\d,\.]+$", s):
        return s

    # Skip global noise labels
    if _GLOBAL_NOISE_RE.match(s):
        return ""

    # Skip known table/form header labels
    if _STOP_HEADER_RE.match(s):
        return ""

    # Skip standalone row numbers
    if _ROW_NUM_RE.match(s):
        return ""

    # Skip GPS coordinates
    if _GPS_RE.match(s):
        return ""

    # Strip trailing merged-row junk: SL-PMA-... DISAHKAN Lihat etc.
    s = re.split(r"(?i)\s+(?:SL-?PMA-\S+|Lihat\s+Loj|DISAHKAN\b)", s, maxsplit=1)[0]
    s = s.strip(" -_/,|")

    return s.strip() if s.strip() else ""


# ---------------------------------------------------------------------------
# Generic column extractor
# ---------------------------------------------------------------------------
def _extract_column(text: str, header_variants: list, max_chars: int = 6000) -> list:
    """
    Find the last occurrence of any header variant in `text`, then collect
    every non-empty, non-noise line below it until another known column
    header is encountered.  Returns a list of cleaned string values.
    """
    # Build a regex that matches any of this column's headers
    col_re = re.compile(
        r"(?i)" + "|".join(f"(?:{p})" for p in header_variants)
    )

    # Find all occurrences; prefer the last one (usually the cleanest column)
    matches = list(col_re.finditer(text))
    if not matches:
        return []

    vals: list[str] = []
    for m in reversed(matches):
        # Skip to end of the header line
        after = text[m.end():]
        nl = after.find("\n")
        after = after[nl + 1:] if nl >= 0 else after

        # Cap how far we scan
        snippet = after[:max_chars]

        current = ""  # accumulate wrapped lines (e.g. "A12 - ESKALATOR /\nLALUAN GERAK")

        for raw_ln in snippet.split("\n"):
            ln = raw_ln.strip()

            if not ln:
                # blank line → flush wrapped accumulator
                if current:
                    v = _clean_value(current)
                    if v:
                        vals.append(v)
                    current = ""
                continue

            # Stop at any known column/section header
            if _STOP_HEADER_RE.match(ln):
                break

            # Lines that contain merged-row noise markers → stop
            if _STOP_INLINE_RE.search(ln) and len(ln) > 60:
                break

            # Wrapped continuation: previous line ends with '/'
            if current and current.rstrip().endswith("/"):
                current = f"{current} {ln}"
                continue

            # Flush previous line and start new
            if current:
                v = _clean_value(current)
                if v:
                    vals.append(v)
            current = ln

        # Flush last accumulated line
        if current:
            v = _clean_value(current)
            if v:
                vals.append(v)

        if vals:
            break  # got data from this occurrence; done

    return vals


# ---------------------------------------------------------------------------
# Expand / pad a column list to match plant count
# ---------------------------------------------------------------------------
def _pad_column(vals: list, target: int) -> list:
    """Cycle through `vals` to fill `target` rows (for repeated categories etc.)"""
    if not vals:
        return [""] * target
    if len(vals) >= target:
        return vals[:target]
    # Cycle
    out = []
    for i in range(target):
        out.append(vals[i % len(vals)])
    return out


# ---------------------------------------------------------------------------
# Header (single-value) field extractor
# ---------------------------------------------------------------------------
def _extract_header_field(text: str, header_variants: list, max_lines: int = 3) -> str:
    """
    Extract the value that appears on the line(s) immediately after a form
    field label. Returns the first non-empty, non-noise value found.
    """
    pat = re.compile(
        r"(?i)(?:" + "|".join(f"(?:{p})" for p in header_variants) + r")"
        r"\s*[:\.]?\s*\n\s*([^\n]+)"
    )
    # Try inline colon version too
    pat2 = re.compile(
        r"(?i)(?:" + "|".join(f"(?:{p})" for p in header_variants) + r")"
        r"\s*[:\.]?\s+([^\n]{2,80})"
    )
    for p in (pat, pat2):
        m = p.search(text)
        if m:
            v = _clean_value(m.group(1))
            if v:
                return v
    return ""


# ---------------------------------------------------------------------------
# Common Malaysian address OCR typo corrections
# ---------------------------------------------------------------------------
def _clean_address_typos(s: str) -> str:
    replacements = {
        r"\bJln Piu\b": "Jln PJU",
        r"\bJLN PIU\b": "JLN PJU",
        r"\bjln piu\b": "jln pju",
        r"\bPju 14/": "PJU 1A/",
        r"\bPJU 14/": "PJU 1A/",
        r"\bpju 14/": "pju 1a/",
        r"\bPetaling Java\b": "Petaling Jaya",
        r"\bPETALING JAVA\b": "PETALING JAYA",
        r"\bpetaling java\b": "petaling jaya",
        r"\bKuala Lampur\b": "Kuala Lumpur",
        r"\bKUALA LAMPUR\b": "KUALA LUMPUR",
    }
    for pat, rep in replacements.items():
        s = re.sub(pat, rep, s)
    return s


# ---------------------------------------------------------------------------
# Address extraction (special: multi-part Poskod + Bandar + Negeri)
# ---------------------------------------------------------------------------
def _extract_address(text: str, text_thresh: str = "") -> str:
    """Extract street address + postcode + city + state as one string."""

    def _skip(ln: str) -> bool:
        low = ln.lower().strip()
        if not low:
            return True
        if low in ("alamat kedudukan", "alamat surat-menyurat"):
            return True
        if "tidak sama seperti" in low:
            return True
        if re.match(r"(?i)^(longitud|longitude|latitude|lintang|garis)", low):
            return True
        if _GPS_RE.match(low):
            return True
        return False

    def _streetlike(ln: str) -> bool:
        return bool(
            re.search(
                r"(?i)\b(jln|jalan|no\.?\s*\d|lot\s|lrg|lorong|km\.?|off\s|tingkat|blok|taman|kampung|kg\.?)\b",
                ln,
            )
            or ("," in ln and len(ln) >= 12)
        )

    addr_text = ""
    # Try to grab everything between Alamat Kedudukan and Poskod
    m = re.search(
        r"(?is)Alamat\s+Kedudukan\s*\n(?P<body>.*?)(?=\n\s*Poskod\b)",
        text, re.MULTILINE | re.DOTALL,
    )
    if m:
        raw = [ln.strip() for ln in m.group("body").split("\n") if ln.strip()]
        lines = [ln for ln in raw if not _skip(ln)]
        street = [ln for ln in lines if _streetlike(ln)]
        addr_text = ", ".join(street if street else lines[:2])
    else:
        for ak in reversed(list(re.finditer(r"Alamat\s+Kedudukan\s*\n", text, re.IGNORECASE))):
            after = text[ak.end():]
            collected = []
            for ln in after.split("\n"):
                ln = ln.strip()
                if not ln:
                    continue
                if re.match(r"(?i)^(Poskod|Bandar|Negeri|Pegawai|No\.|Emel|Nama\s+Pemunya|Senarai)", ln):
                    break
                if _skip(ln):
                    continue
                collected.append(ln)
                if len(collected) >= 4:
                    break
            street = [x for x in collected if _streetlike(x)]
            chosen = street if street else collected[:1]
            if chosen:
                addr_text = ", ".join(chosen)
                break

    # Poskod / Bandar / Negeri
    search_text = text_thresh if text_thresh else text
    ak_m = re.search(r"Alamat\s+Kedudukan", search_text, re.IGNORECASE)
    locality = search_text[ak_m.end():] if ak_m else search_text

    poskod = bandar = negeri = ""

    seg = re.search(
        r"(?is)Poskod\s*[:\.]?\s*(?:\n\s*)?\b(\d{4,7})\b"
        r".{0,400}?Bandar\s*[:\.]?\s*(?:\n\s*)?([^\n]+)"
        r".{0,400}?Negeri\s*[:\.]?\s*(?:\n\s*)?([^\n]+)",
        locality, re.DOTALL,
    )
    if seg:
        poskod = seg.group(1).strip()
        bandar = seg.group(2).strip().split("\n")[0].strip().rstrip("|•◆▼").strip()
        negeri_raw = seg.group(3).strip().split("\n")[0]
        # chop off any "Bandar" bleed
        chop = re.search(r"(?i)\s*\bBandar\b", negeri_raw)
        negeri = (negeri_raw[:chop.start()] if chop else negeri_raw).rstrip("|•◆▼").strip()

    # Fallbacks
    if not poskod:
        pk = re.search(r"(?is)\bPoskod\s*[:\.]?\s*(?:\n\s*)?\b(\d{4,7})\b", locality)
        if pk:
            poskod = pk.group(1)
    if not poskod:
        # Fallback: search for a 5-digit postcode near the word Poskod in the text
        poskod_match = re.search(r"(?i)Poskod", search_text)
        if poskod_match:
            pk_m = re.search(r"\b(\d{5})\b", search_text[poskod_match.start():poskod_match.start() + 250])
            if pk_m:
                poskod = pk_m.group(1)

    # If addr_text contains a 5-digit postcode at the end, extract it and strip it
    postcode_in_addr = re.search(r"\b(\d{5})\b\s*$", addr_text)
    if postcode_in_addr:
        if not poskod:
            poskod = postcode_in_addr.group(1)
        addr_text = addr_text[:postcode_in_addr.start()].strip(" ,.")

    if not bandar:
        bm = re.search(r"(?is)\bBandar\s*[:\.]?\s*(?:\n\s*)?([^\n]+)", locality)
        if bm:
            bandar = bm.group(1).strip().split("\n")[0].rstrip("|•◆▼").strip()
    if not bandar:
        # Fallback: search for a city preceding a known state (e.g. "PETALING JAVA SELANGOR")
        states_pat = r"(Selangor|Johor|Kedah|Kelantan|Melaka|Negeri Sembilan|Pahang|Perak|Perlis|Pulau Pinang|Sabah|Sarawak|Terengganu|Kuala Lumpur)"
        bm = re.search(r"(?i)\b([a-zA-Z ]{3,30})\s+" + states_pat + r"\b", locality)
        if bm:
            bandar = bm.group(1).strip().title()
            if not negeri:
                negeri = bm.group(2).strip().title()

    if not negeri:
        nm = re.search(r"(?is)\bNegeri\s*[:\.]?\s*(?:\n\s*)?([^\n]+)", locality)
        if nm:
            negeri = nm.group(1).strip().split("\n")[0].rstrip("|•◆▼").strip()
    if not negeri:
        # Pilihan IKKP Negeri fallback (case-insensitive search restricted to single line to avoid bleed)
        ikkp = re.search(r"(?i)Pilihan\s+IKKP\s+Negeri[^\n]*\n\s*([A-Z][A-Z ]{2,30})", search_text)
        if ikkp:
            negeri = ikkp.group(1).strip().title()

    addr_text = addr_text.strip(". ,")
    result = ", ".join(
        x for x in (
            addr_text,
            " ".join(p for p in (poskod, bandar) if p),
            negeri,
        ) if x
    )
    return _clean_address_typos(result)


# ---------------------------------------------------------------------------
# Owner registration number (special: complex multi-pattern fallback)
# ---------------------------------------------------------------------------
def _extract_owner_reg(text: str) -> str:
    t = unicodedata.normalize("NFKC", text).replace("\xa0", " ").replace("|", "\n")

    patterns = [
        # Broadest: any OCR corruption of 'Pendaf/Pendatt/Pendataran Pemunya'
        r"(?mis)No\.?\s*Pend\w+\s+P[eе]mun\w+\s*[:\.]?\s*\r?\n\s*([^\n\r]+?)(?:\r?\n|$)",
        # Inline colon version
        r"(?mis)No\.?\s*Pend\w+\s+P[eе]mun\w+\s*[:\.][\s]+([^\n\r]+?)(?:\r?\n|$)",
        # All caps
        r"(?mis)\bNO\.?\s+PEND\w+\s+PEMUNYA\s*[:\.]?\s*\r?\n\s*([^\n\r]+?)(?:\r?\n|$)",
        # Just 'Pemunya' with No. prefix — last resort
        r"(?mis)No\.?\s*\w*\s*P[eе]mun\w+\s*[:\.]?\s*\r?\n\s*([^\n\r]{3,})(?:\r?\n|$)",
    ]

    val = ""
    for p in patterns:
        m = re.search(p, t)
        if m:
            v = m.group(1).strip()
            if v and len(v) >= 4:
                val = v
                break

    if val:
        bleed = re.split(
            r"(?i)\s+(?=Alamat\s+Kedudukan|Pegawai\s+Dihubungi|No\.?\s*Telefon|Poskod|Bandar|Maklumat)",
            val, maxsplit=1,
        )
        val = bleed[0].strip()

    val = re.sub(r"^[:\.\;\,\|•◆▼\s\-]+|[:\.\;\,\|•◆▼\s\-]+$", "", val)
    return val.strip()


# ---------------------------------------------------------------------------
# Plant registration numbers (keep pattern-based: SL-PMA-xxxxx is reliable)
# ---------------------------------------------------------------------------
def _extract_plant_reg_nos(text: str) -> list:
    # Try column listing first
    vals = _extract_column(text, [r"No\.?\s*P[oe]ndaftaran\s+Loji"])
    # Keep only lines that look like registration numbers (contain digits/dashes)
    filtered = [v for v in vals if re.search(r"\d", v) and len(v) >= 5]
    if filtered:
        return filtered

    # Fallback: find SL-PMA-... / XX-PMA-... patterns anywhere
    found = re.findall(r"[A-Z]{2,}-PM[A-Z0-9\-]+", text)
    seen: set = set()
    unique = [x for x in found if not (x in seen or seen.add(x))]  # type: ignore[func-returns-value]
    return unique


# ---------------------------------------------------------------------------
# Expiry dates
# ---------------------------------------------------------------------------
def _extract_dates(text: str) -> list:
    # Try from Tarikh Luput column first
    col = _extract_column(text, [r"Tarikh\s+(?:Luput|Tamat|CF)", r"Luput\s+CF", r"Expiry"])
    date_vals = [v for v in col if re.match(r"\d{2}/\d{2}/\d{4}", v)]
    if date_vals:
        return date_vals
    # Fallback: all DD/MM/YYYY in text
    return re.findall(r"\b\d{2}/\d{2}/\d{4}\b", text)


# ---------------------------------------------------------------------------
# Clean Caj Category Column from visual OCR noise / misreads
# ---------------------------------------------------------------------------
def _clean_category(val: str) -> str:
    val = val.strip()
    # Normalize spaces and dashes
    val = re.sub(r"\s+", " ", val)
    val = re.sub(r"[-–—~=)]+", "-", val)
    
    # 1. Clean A11 - LIF variations
    if re.search(r"(?i)\b(A[N1]{2}|AN|An)\b.*?\bL[I]?F\b", val):
        return "A11 - LIF"
    
    # 2. Clean A12 - ESKALATOR / LALUAN GERAK variations
    if re.search(r"(?i)\b(A[I1l]2|Al2|AI2|A12)\b.*?\bESKALATOR\b", val):
        return "A12 - ESKALATOR / LALUAN GERAK"
        
    return val


# =============================================================================
# MAIN ENTRY POINT
# =============================================================================
def extract_images(file_paths):
    try:
        text_parts = []
        text_parts_thresh = []
        for fp in file_paths:
            img = Image.open(fp)
            # 1. Default OCR text
            text_parts.append(pytesseract.image_to_string(img))
            # 2. Thresholded/Binarized OCR text (helps capture grey text input fields/dropdowns)
            thresh_img = img.convert('L').point(lambda x: 0 if x < 180 else 255, '1')
            text_parts_thresh.append(pytesseract.image_to_string(thresh_img))
        text = unicodedata.normalize("NFKC", "\n".join(text_parts))
        text_thresh = unicodedata.normalize("NFKC", "\n".join(text_parts_thresh))

        # ------------------------------------------------------------------
        # 1. Header / form fields (single values)
        # ------------------------------------------------------------------
        nama_pemunya       = _extract_header_field(text, [r"Nama\s+Pemunya"])
        no_pend_pemunya    = _extract_owner_reg(text)
        # Fallback: generic scan for any alphanumeric token after the registration label
        if not no_pend_pemunya:
            _reg_m = re.search(
                r"(?i)No\.?\s*P[eе]ndafta\w*\s+P[eе]mun\w+[^\n]*\n([^\n]+)",
                text
            )
            if _reg_m:
                _cand = _clean_value(_reg_m.group(1))
                if _cand and not re.match(r"(?i)^(alamat|nama|pegawai|no\.|emel)", _cand):
                    no_pend_pemunya = _cand
        maklumat_alamat    = _extract_address(text, text_thresh)
        pegawai_dihubungi  = _extract_header_field(text, [r"Pegawai\s+(?:Yang\s+)?Dihubungi"])

        # Phone: only accept actual phone number digits, not form hint text (no letters)
        _raw_tel = _extract_header_field(text, [r"No\.?\s*(?:,\s*)?Telefon", r"Tel\.?\s*:"])
        # A real phone number contains mostly digits (≥6 digits, ≤20 chars total) and no alphabetic characters
        if _raw_tel and not re.search(r"[a-zA-Z]", _raw_tel) and re.search(r"\d{6,}", _raw_tel.replace("-", "").replace(" ", "")):
            no_telefon = re.sub(r"[^\d\+\-\s]", "", _raw_tel).strip()
        else:
            # Scan all lines for first phone-like value after No. Telefon
            _tel_m = re.search(r"(?i)No\.?\s*(?:,\s*)?Telefon[^\n]*\n", text)
            no_telefon = ""
            if _tel_m:
                for _ln in text[_tel_m.end():].split("\n")[:8]:
                    _ln = _ln.strip()
                    if re.match(r"[\d\+][\d\s\-\+]{5,19}$", _ln):
                        no_telefon = _ln
                        break

        # ------------------------------------------------------------------
        # 2. Table columns (lists — one value per plant row)
        # ------------------------------------------------------------------
        plant_reg_nos = _extract_plant_reg_nos(text)
        dates         = _extract_dates(text)

        # Generic columns — no content keyword restrictions
        kategori_caj  = _extract_column(text, [r"Kat[eo]gori\s+Caj", r"Kategori\s*Caj\s*[-–]\s*Jenis"])
        kategori_caj  = [_clean_category(v) for v in kategori_caj]
        sub_jenis     = _extract_column(text, [r"Sub\s+J[oe]nis\s*(?:Loji)?"])
        kod_loji      = _extract_column(text, [r"Kod\s+Loji", r"Status\s+Loji"])
        no_siri_loji  = _extract_column(text, [r"No\.?\s*Siri\s*(?:Loji)?"])

        # Use all Kadar variants from TABLE_COLUMNS for rate
        kadar_caj     = _extract_column(text, [
            r"Kadar\s*(?:Caj)?(?:\s*\(.*?\))?\s*",
            r"Kadar\s*\(RM",
            r"Rate\s*(?:\(.*?\))?\s*",
            r"Kadar\b",
        ])
        # Filter kadar to only numeric/money values if mixed with other text
        kadar_caj = [v for v in kadar_caj if re.search(r"[\d]", v)]

        # Kod Loji fallback: short alphanumeric codes only (e.g. "PMA", "PE")
        if not kod_loji:
            kod_loji = []
        else:
            kod_loji = [v for v in kod_loji if len(v) <= 10]

        # No. Siri fallback: try merged-row pattern (PMA <serial> DD/MM/YYYY)
        if len(no_siri_loji) < len(plant_reg_nos):
            merged_re = re.compile(
                r"(?:^|\n)\s*\d+\s+SL-PMA-\S+.*?\bPMA\b\s+([A-Z0-9][A-Z0-9\-/]{3,20})\s+(\d{2}/\d{2}/\d{4})",
                re.IGNORECASE,
            )
            merged_serials = []
            seen_s: set = set()
            for mr in merged_re.finditer(text):
                s = mr.group(1).strip()
                if (s not in plant_reg_nos
                        and not re.match(r"\d{2}/\d{2}/\d{4}", s)
                        and not re.match(r"(?i)^(PMA|LIF|ESK|SEMAKAN|LIHAT|DISAHKAN)$", s)
                        and s not in seen_s):
                    merged_serials.append(s)
                    seen_s.add(s)
            if merged_serials:
                no_siri_loji = merged_serials

        # ------------------------------------------------------------------
        # 3. Determine row count & pad all columns
        # ------------------------------------------------------------------
        num_plants = len(plant_reg_nos) if plant_reg_nos else 1

        kategori_caj = _pad_column(kategori_caj, num_plants)
        sub_jenis    = _pad_column(sub_jenis,    num_plants)
        kod_loji     = _pad_column(kod_loji,     num_plants)
        no_siri_loji = _pad_column(no_siri_loji, num_plants)
        dates        = _pad_column(dates,         num_plants)
        kadar_caj    = _pad_column(kadar_caj,     num_plants)

        # ------------------------------------------------------------------
        # 4. Build output rows
        # ------------------------------------------------------------------
        rows = []
        rows.append(["0","1","2","3","4","5","6","7","8","9","10","11","12","13","14"])

        for i in range(num_plants):
            plant_reg = plant_reg_nos[i] if i < len(plant_reg_nos) else ""
            row = [
                str(i + 1),
                nama_pemunya      if i == 0 else "",   # 1: Company
                maklumat_alamat   if i == 0 else "",   # 2: Address
                no_pend_pemunya   if i == 0 else "",   # 3: Owner Reg No.
                pegawai_dihubungi if i == 0 else "",   # 4: PIC
                no_telefon        if i == 0 else "",   # 5: Contact
                plant_reg,                             # 6: Plant Reg No.
                kategori_caj[i],                       # 7: Category
                sub_jenis[i],                          # 8: Sub Type
                kod_loji[i],                           # 9: Plant Code
                no_siri_loji[i],                       # 10: Machine Serial
                dates[i],                              # 11: CF Expiry Date
                kadar_caj[i],                          # 12: Rate
            ]
            rows.append(row)

        tables = [rows] if len(rows) > 1 else []
        print(json.dumps({"tables": tables}))

    except Exception as e:
        print(json.dumps({"error": str(e)}))
        sys.exit(1)


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No file paths provided"}))
        sys.exit(1)
    extract_images(sys.argv[1:])
