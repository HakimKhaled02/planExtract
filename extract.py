import sys
import json
import re
import warnings
import unicodedata
from PIL import Image
import pytesseract

warnings.filterwarnings("ignore")


# --- Kategori Caj — values like "A11 - LIF", "A12 - ESKALATOR / LALUAN GERAK".
# OCR merges rows into PMA lines sometimes ("An-" ≈ "A11-"). Allow DUPLICATE lines
# when several plants share the same category column value.

_CHARGE_LINE_STRICT = re.compile(
    r"(?is)^\s*[\'\"]?\s*A\s*(\d{1,2})\s*-\s*(.+)$",
)


def _normalize_charge_code_prefix(s: str) -> str:
    """Normalize common OCR slips on the AX - portion."""
    if not s:
        return ""
    x = unicodedata.normalize("NFKC", s).strip()
    x = x.strip("\"'`«»‚")
    # 'An -' → 'A11 -' ONLY when no digit follows (avoid turning A12 into A11)
    x = re.sub(r"(?is)^An\s*-\s*(?!\d)", "A11 - ", x)
    # AI2 / Al2 / A|2 → A12 (OCR confuses 1 with I/l/|)
    x = re.sub(r"(?is)^['\"]?A[Il|]\s*2\s*-\s*", "A12 - ", x)
    x = re.sub(r"(?is)^['\"]?A[Il|]\s*3\s*-\s*", "A13 - ", x)
    x = re.sub(r"(?is)^['\"]?A[Il|]\s*4\s*-\s*", "A14 - ", x)
    # 'A 12' with space → 'A12'
    x = re.sub(r"(?is)^A\s+(\d{1,2})\s*-\s*", lambda m: f"A{m.group(1)} - ", x)
    return x


def _squash_charge_category_tail(s: str) -> str:
    """Column often shows only 'A11 - LIF'; PENUMPANG appears in Sub Jenis.
    Also normalizes OCR artefact 'LF' -> 'LIF'."""
    t = unicodedata.normalize("NFKC", (s or "")).strip()
    t = re.sub(r"\s+", " ", t)
    t = re.sub(r"(?i)\blif\b\s+penumpang\b(?:\s+.*)?$", "LIF", t.strip())
    # OCR sometimes reads 'LIF' as 'LF' — expand it back
    t = re.sub(r"(?i)\bLF\b", "LIF", t)
    return t.strip()


def _finalize_kategori_caj(tok: str) -> str:
    tok = _normalize_charge_code_prefix(tok)
    tok = tok.split("|", maxsplit=1)[0]
    tok = re.sub(r"\s+", " ", tok)
    chop = re.split(r"(?i)\s+PMA\b|[\|\\]\s*\d\s+[Ss]|Lihat\b|DISAHKAN", tok, maxsplit=1)[0].strip()
    chop = re.sub(r"\b(?i)(\w{2,})\s+\1\b", r"\1", chop)
    chop = _squash_charge_category_tail(chop)
    chop = chop.strip(" -_/,")
    return chop


def _extract_clean_kategori_line(ln: str) -> str:
    raw = unicodedata.normalize("NFKC", ln).strip()
    raw = raw.strip("\"'`«»‚")
    if not raw or len(raw) > 240:
        return ""

    if re.search(
        r"(?is)SL-?\s*PMA|PMA-\d|^[\d]+\s+SL-|PMA\s+\d+|SL-PMA|Lihat\b|DISAHKAN\b|\|\s+PMA|PMA\s+PMA",
        raw,
    ):
        return ""

    dn = re.sub(r"[‑–−]", "-", raw)
    if "-" not in dn:
        return ""

    dn = _normalize_charge_code_prefix(dn.strip())
    m = _CHARGE_LINE_STRICT.match(dn.strip())
    if not m:
        return ""

    num = m.group(1).strip()
    rhs = m.group(2).strip()
    if not num or not rhs:
        return ""
    cand = _finalize_kategori_caj(f"A{num} - {rhs}")
    return _squash_charge_category_tail(cand)


_CAT_MERGED_RE = re.compile(
    r"(?is)"
    r"(?<![A-Za-z0-9])(?:An\b|[Aa]'?\s*(?:Ii?)?[Oo1lIi\d]?['\"]?\s*|\d{2,})\s*"
    r"-\s*"
    r"(.+?)"
    r"(?=\s+PMA\b|$|[\|\\]\s*\d\s+[Ss]|$|\|\s+PMA|\n|'?\s*(?:An|\b[Aa]\s*[IiOo\d]|\s*\d{2,})\s*-)",
)


def _is_kategori_caj_token(tok: str) -> bool:
    if not tok or len(tok) < 5:
        return False
    tl = tok.lower().strip("\"'`")
    if "sl-pma" in tl or tl.startswith("sl-"):
        return False
    if "disahkan" in tl:
        return False
    if re.search(r"(?is)PMA-|^\d+\s+sl-|lihat\s+lo|\|\s+[a-z]{2}\s+PMA", tl):
        return False
    nt = unicodedata.normalize("NFKC", tl).strip()
    if not re.search(r"(?i)^a\s*\d{1,2}\s*-", nt):
        return False

    tail = nt.split("-", 1)[-1] if "-" in nt else nt
    tl2 = tail.lower()
    keys = ("eskalator", "laluan", "gerak", "penumpang", "caluan", "angkat", "pintu", "elevator", "mesin", "tangga", "lif", " lf", "-lf")
    return any(k in tl2 for k in keys) or tl2.strip() in ("lif", "lf")


def _looks_like_kategori_start_line(line: str) -> bool:
    s = unicodedata.normalize("NFKC", (line or "")).strip()
    if not s:
        return False
    s = re.sub(r"[‑–−]", "-", s)
    s = s.strip("\"'`«»‚")
    # A line that ends with '/' is a wrapped continuation (e.g. 'A12 - ESKALATOR /')
    # — don't count it as a standalone new category start that would flush current.
    # It IS still a start if it's the very first token we see (current == '').
    return bool(
        re.match(r"(?i)^A\s*\d{1,2}\s*-\s*", s)
        or re.match(r"(?i)^A[Il|]\s*\d\s*-\s*", s)
        or re.match(r"(?i)^An\s*-\s*", s)
    )


def _expand_charge_list_to_rows(vals: list, target_n: int) -> list:
    """Repeat the ordered pattern [A11,A11,A12,A12] instead of stamping the last token only."""
    vals = [v.strip() for v in vals if v and v.strip()]
    if target_n <= 0:
        return []
    if not vals:
        return [""] * target_n

    base = vals[:]
    out: list[str] = []
    j = 0
    while len(out) < target_n:
        out.append(base[j % len(base)])
        j += 1

    return out[:target_n]


def _extract_kategori_caj_ordered(cat_slab: str, plant_reg_count: int) -> list:
    blob = unicodedata.normalize("NFKC", cat_slab or "").replace("|", "\n")

    vals: list[str] = []

    logical_lines: list[str] = []
    current = ""
    for ln in blob.split("\n"):
        s = ln.strip()
        if not s:
            continue
        # Strip curly/smart quotes that OCR inserts before category codes
        # e.g. \u2018Al2 - ESKALATOR / (left single quotation mark) -> Al2 - ESKALATOR /
        s = s.strip("\u2018\u2019\u201a\u201c\u201d\u201e\u00ab\u00bb\"'`")
        if not s:
            continue
        slow = s.lower()
        if "kategori caj" in slow and "-" not in s:
            continue
        if re.search(r"(?i)^sub\s+j[oe]nis\b|^kod\s+loji\b|^status\s+loji\b|^no\.?\s+siri\b", s):
            break

        # A line that starts a new Ax - category AND we have an in-progress
        # line that does NOT end with '/' (open continuation), flush it.
        is_start = _looks_like_kategori_start_line(s)
        prev_continues = current.rstrip().endswith("/")

        if is_start and not prev_continues:
            if current:
                logical_lines.append(current.strip())
            current = s
            continue

        # Wrapped OCR line (e.g. "LALUAN GERAK" after "A12 - ESKALATOR /") —
        # also handles case where prev line ends with '/' and this is a start-like
        # token but is actually the tail of the previous value.
        if current:
            if not re.search(r"(?is)SL-?\s*PMA|PMA-\d|^[\d]+\s+SL-|Lihat\b|DISAHKAN\b", s):
                current = f"{current} {s}".strip()
            continue

        logical_lines.append(s)

    if current:
        logical_lines.append(current.strip())

    for ln in logical_lines:
        cand = _extract_clean_kategori_line(ln)
        if cand and _is_kategori_caj_token(cand):
            vals.append(cand)


    merged_hits = 0
    max_merged_to_try = max(plant_reg_count * 3, 24)
    merged_only_if_short = bool(plant_reg_count and len(vals) < plant_reg_count and not vals)

    if merged_only_if_short:
        for mm in _CAT_MERGED_RE.finditer(blob):
            merged_hits += 1
            if merged_hits > max_merged_to_try:
                break
            c = _finalize_kategori_caj(mm.group())
            if c and _is_kategori_caj_token(c):
                vals.append(c)
                if merged_hits <= plant_reg_count * 6 and len(vals) >= plant_reg_count * 8:
                    break

    need = plant_reg_count if plant_reg_count else max(len(vals), 1)
    return _expand_charge_list_to_rows(vals, need)


def _header_after_nama_pemunya(blob: str) -> str:
    """OCR slab after company identity through Alamat Kedudukan (exclusive)."""
    nm = re.search(r"(?mis)Nama\s+Pemunya\b", blob)
    if not nm:
        return ""

    tail = blob[nm.end() :].lstrip()
    nl = tail.find("\n")

    after_company = tail[nl + 1 :] if nl >= 0 else ""

    ak = re.search(r"(?mis)(?:^\s*)Alamat\s+Kedudukan\b", after_company, re.MULTILINE)
    end = ak.start() if ak else min(len(after_company), 2800)

    slab = after_company[:end]

    ak2 = re.search(r"(?mis)(?:^\s*)Alamat\s+Kedudukan\b", tail, re.MULTILINE)
    if not slab.strip() and ak2 is not None:

        slab = tail[: ak2.start()]

    return slab


def _looks_like_my_registration_candidate(s: str) -> bool:
    s = s.strip()
    if len(s) < 5 or len(s) > 72:
        return False
    if re.search(r"(?i)^(alamat|nama|bandar|negeri|poskod)\b", s):
        return False
    if re.search(r"(?i)jalan|jaln|kedudukan|pegawai|telefon|maklumat|pendaft", s):
        return False
    if re.search(r"\d", s) or "-" in s or "/" in s or "(" in s:
        return True
    return bool(len(s) >= 8 and re.match(r"(?i)^[A-Z0-9][A-Z0-9\-\s]{4,}$", s))


def _parse_no_pemunya_registration(blob: str) -> str:
    """
    Normalize and match No. Pendaftaran Pemunya with typical OCR breakage.
    Prefer the OCR region between Nama Pemunya and Alamat Kedudukan.
    """
    t = unicodedata.normalize("NFKC", blob)
    t = t.replace("\xa0", " ").replace("|", "\n")

    slab = _header_after_nama_pemunya(t)
    search_spaces = []
    if slab.strip():
        search_spaces.append(slab.strip())
    search_spaces.append(t)

    pem_pat = (
        r"(?mis)No\.?\s*Nombor\s+Pendaftaran\s+Pemunya\s*[:\.]?\s*\r?\n\s*([^\n\r]+?)(?:\r?\n|$)",
        r"(?mis)(?:Nombor\s*)?(?:No\.?\s*)?P[eе]ndafta[roi]?\w*\s+P[eе]mun(?:ya|yv|mys|wny)\s*[:\.]?\s*\r?\n\s*([^\n\r]+?)(?:\r?\n|$)",
        r"(?mis)No\.?\s*P[eе]ndafta[roi]?\w*\s+P[eе]mun(?:ya|yv|mys|wny)\s*[:\.]?\s*\r?\n\s*([^\n\r]+?)(?:\r?\n|$)",
        r"(?mis)No\.?\s*P[eе]ndafta[roi]?\w*\s+P[eе]mun(?:ya|yv|mys|wny)\s*[:\.]+\s*([^\n\r]+?)(?:\r?\n|$)",
        r"(?mis)\bNO\.?\s+PENDAFTARAN\s+PEMUNYA\s*[:\.]?\s*\r?\n\s*([^\n\r]+?)(?:\r?\n|$)",
        r"(?mis)\bNO\.?\s+PENDAFTARAN\s+PEMUNYA\s*[:\.]+\s*([^\n\r]+?)(?:\r?\n|$)",
        r"(?mis)No\.?\s*P[eе]ndafta[roi]?\w*\s+P[eе]mun(?:ya|yv|mys|wny)\s+([^\n\r:]+?)(?:\r?\n|$)",
        r"(?mis)No\.?\s*P[eе]ndafta[roi]?\w*\s*\r?\n\s*P[eе]mun(?:ya|yv|mys|wny)\s*[:\.]?\s*\r?\n?\s*([^\n\r]+?)(?:\r?\n|$)",
        r"(?mis)No\.?\s*P[eе]ndafta[roi]?\w*\s*\r?\n\s*P[eе]mun(?:ya|yv|mys|wny)\s*[:\.]+\s*([^\n\r]+?)(?:\r?\n|$)",
    )

    no_pem = ""
    for space in search_spaces:
        for rp in pem_pat:
            reg_match = re.search(rp, space)
            if reg_match:
                no_pem = reg_match.group(1).strip()
                if no_pem:
                    break
        if no_pem:
            break

    if not no_pem and slab.strip():
        block_lines = [ln.strip() for ln in slab.replace("\r", "\n").split("\n") if ln.strip()]
        max_k = len(block_lines)
        for idx in range(max_k):
            window = "\n".join(block_lines[idx : min(max_k, idx + 4)])
            lw = window.lower().replace("_", "")
            if "pendaft" not in lw:
                continue
            if not re.search(
                r"(?is)pemuny|pernunya?|pemunn|pemmyv|pemuv|pemwny",
                lw,
                re.I,
            ):
                continue
            for off in (1, 2, 3):
                if idx + off >= max_k:
                    break
                cand = block_lines[idx + off].strip()
                if _looks_like_my_registration_candidate(cand):
                    no_pem = cand
                    break
            if no_pem:
                break

    if no_pem:
        bleed = re.split(
            r"(?i)\s+(?=Alamat\s+Kedudukan|Pegawai\s+Dihubungi|No\.?\s*Telefon|Poskod|Bandar|Maklumat)",
            no_pem,
            maxsplit=1,
        )
        no_pem = bleed[0].strip()

    no_pem = re.sub(r"^[:\.;,\|•◆▼\s\-]+|[:\.;,\|•◆▼\s\-]+$", "", no_pem)
    return no_pem.strip()


def extract_images(file_paths):
    try:
        text_parts = []
        for fp in file_paths:
            img = Image.open(fp)
            text_parts.append(pytesseract.image_to_string(img))
        text = unicodedata.normalize("NFKC", "\n".join(text_parts))
        
        # Dictionary to store extracted data
        data = {
            "nama_pemunya": "",
            "maklumat_alamat": "",
            "no_pendaftaran_pemunya": "",
            "pegawai_dihubungi": "",
            "no_telefon": ""
        }
        
        # 1. Extract Header Information using regex
        
        # Company
        company_match = re.search(r"Nama Pemunya\s*\n(.*?)\n", text, re.IGNORECASE)
        if company_match:
            data["nama_pemunya"] = company_match.group(1).strip()
            
        data["no_pendaftaran_pemunya"] = _parse_no_pemunya_registration(text)
            
        # Residential address lines sit after "Alamat Kedudukan" and before "Poskod".
        # Skip mailing-address notices, duplicated labels, and GPS rows (Lintang/Longitud
        # often render between Kedudukan and Poskod in OCR).
        def _alamat_skip_line(line: str) -> bool:
            low = line.lower().strip()
            if not low:
                return True
            if (
                low == "alamat kedudukan"
                or "alamat surat-menyurat" in low
                or "tidak sama seperti alamat kedudukan" in low
            ):
                return True
            # Coordinate field labels / values (Spanish/Malay OCR “Longitud” etc.)
            if re.match(r"(?i)^(longitud|longitude|latitude|lintang|latitud)\s*:?$", low):
                return True
            if re.search(r"(?i)\b(longitud|longitude|latitude|lintang)\b", low) and len(low) <= 22:
                return True
            if re.match(r"(?i)^(garis\s*bujur|garis\s*lintang)\s*:?$", low):
                return True
            # Typical lat/lng numbers only (4+ fractional digits)
            if re.match(r"^-?\d{1,3}\.\d{4,}$", low):
                return True
            return False

        def _line_looks_like_street(ln: str) -> bool:
            return bool(
                re.search(
                    r"(?i)\b(jln|jalan|no\.?\s*\d|lot\s|lrg|lorong|km\.?|off\s|tingkat|blok|taman|kampung|kg\.?)\b",
                    ln,
                )
                or ("," in ln and len(ln) >= 12)
            )

        addr_text = ""
        # Stop street block before "Poskod" — allow postcode on next line OR same line (OCR varies).
        addr_until_poskod = re.search(
            r"(?is)Alamat\s+Kedudukan\s*\n(?P<body>.*?)"
            r"(?=^\s*Poskod\b|\n\s*Poskod\b)",
            text,
            re.MULTILINE | re.DOTALL,
        )
        if addr_until_poskod:
            raw = [ln.strip() for ln in addr_until_poskod.group("body").split("\n") if ln.strip()]
            addr_lines = [ln for ln in raw if not _alamat_skip_line(ln)]
            streetish = [ln for ln in addr_lines if _line_looks_like_street(ln)]
            addr_text = ", ".join(streetish if streetish else addr_lines)
        else:
            # Try all 'Alamat Kedudukan' headers in reverse order (prefer the last one,
            # which is closest to the actual data line after all the label repetitions).
            all_ak = list(re.finditer(r"Alamat\s+Kedudukan\s*\n", text, re.IGNORECASE))
            for ak_m in reversed(all_ak):
                cand = text[ak_m.end():].split("\n")[0].strip()
                if cand and not _alamat_skip_line(cand) and _line_looks_like_street(cand):
                    addr_text = cand
                    break

            # If still empty, try collecting a few lines after the best Alamat Kedudukan
            if not addr_text and all_ak:
                for ak_m in reversed(all_ak):
                    after = text[ak_m.end():]
                    collected = []
                    for ln in after.split("\n"):
                        ln = ln.strip()
                        if not ln:
                            continue
                        # Stop at section headers, form labels, plant table, or next major block
                        if re.match(
                            r"(?i)^(=\s*Maklumat|Poskod|Bandar|Negeri|Pegawai\s+Dihubungi|"
                            r"No\.\s*Faks|No\.\s*Telefon|Emel|Nama\s+Pemunya|Senarai\s+Loji|"
                            r"No\.\s+Pendaftaran\s+Loji|Maklumat\s+Hubungan)\b",
                            ln,
                        ):
                            break
                        if _alamat_skip_line(ln):
                            continue
                        collected.append(ln)
                        # Stop after collecting enough street lines
                        if len(collected) >= 4:
                            break
                    street_fb = [x for x in collected if _line_looks_like_street(x)]
                    chosen = street_fb if street_fb else collected[:1]
                    if chosen:
                        addr_text = ", ".join(chosen)
                        break


        # Poskod / Bandar / Negeri: OCR often merges label+value (“Poskod 47301”) or uses ":".
        def _normalize_local_part(s):
            """Strip OCR noise trailing state/city bleed from dropdown neighbours."""
            s = (s or "").strip()
            # Drop trailing separators / junk from same OCR line as next label
            s = re.sub(r"[|•◆▼]+$", "", s).strip()
            return s

        def _extract_my_locality(snip: str):
            """Return (poskod, bandar, negeri) after Alamat Kedudukan block."""
            if not snip:
                return "", "", ""

            pk = re.search(
                r"(?is)\bPoskod\s*[:\.]?\s*(?:\n\s*)?\b(\d{5})\b",
                snip,
            )
            if not pk:
                pk = re.search(
                    r"(?is)\bPoskod\s*[:\.]?\s*(?:\n\s*)?\b(\d{4,7})\b",
                    snip,
                )
            posk_val = pk.group(1) if pk else ""

            scan = snip[pk.start() :] if pk else snip
            wm = scan[: min(len(scan), 1200)]

            bm = re.search(r"(?is)\bBandar\s*[:\.]?\s*(?:\n\s*)?(.+)", wm)
            bandar_val = ""
            if bm:
                chunk = bm.group(1).strip()
                chop = re.search(r"(?is)\s*\bNegeri\b", chunk)
                if chop:
                    chunk = chunk[: chop.start()].strip()
                bandar_val = _normalize_local_part(chunk.split("\n")[0])

            nm = re.search(r"(?is)\bNegeri\s*[:\.]?\s*(?:\n\s*)?(.+)", wm)
            negeri_val = ""
            if nm:
                negeri_raw = nm.group(1).strip().split("\n")[0]
                negeri_val = _normalize_local_part(negeri_raw)

            return posk_val, bandar_val, negeri_val

        ak_hdr = re.search(r"Alamat\s+Kedudukan\s*\n", text, re.IGNORECASE)
        locality_tail = text[ak_hdr.end() :] if ak_hdr else text

        poskod, bandar, negeri = "", "", ""

        pos_seg = re.search(
            r"(?is)Poskod\s*[:\.]?\s*(?:\n\s*)?\b(\d{4,7})\b"
            r".{0,400}?Bandar\s*[:\.]?\s*(?:\n\s*)?(?:[^\n]+\n\s*)?\s*([^\n]+)"
            r".{0,400}?Negeri\s*[:\.]?\s*(?:\n\s*)?\s*([^\n]+)",
            locality_tail,
            re.DOTALL,
        )
        if pos_seg:
            bandar_seg = pos_seg.group(2).strip().split("\n")[0].strip()
            negeri_seg = pos_seg.group(3).strip().split("\n")[0].strip()
            chop = re.search(r"(?is)\s*\bBandar\b", negeri_seg)
            if chop:
                negeri_seg = negeri_seg[: chop.start()].strip()
            poskod, bandar, negeri = (
                pos_seg.group(1).strip(),
                _normalize_local_part(bandar_seg),
                _normalize_local_part(negeri_seg),
            )

        if not poskod or not bandar or not negeri:
            fk, fb, fn = _extract_my_locality(locality_tail)
            poskod = poskod or fk
            bandar = bandar or fb
            negeri = negeri or fn

        if not poskod:
            # Last resort: first 5-digit token near the address section.
            # Limit to 600 chars to avoid picking up plant reg numbers like SL-PMA-78369.
            snippet = locality_tail[:600]
            for rm in re.finditer(r"(?<![-/])(?<!\d)\b(\d{5})\b(?!\d)", snippet):
                cand = rm.group(1)
                line_start = snippet.rfind("\n", 0, rm.start()) + 1
                line_snip = snippet[line_start : rm.end() + 30]
                # Skip if on a line that looks like a lat/lng or plant registration
                if re.search(r"-?\d+\.\d{4}|SL-PMA|Lihat|DISAHKAN", line_snip):
                    continue
                # Skip if the digit immediately before is a dash (part of reg number e.g. -78369)
                pre = snippet[max(0, rm.start()-1):rm.start()]
                if pre in ('-', '/'):
                    continue
                poskod = cand
                break

        # Strip trailing punctuation from street address (OCR often adds '.' at end)
        addr_text = addr_text.strip(". ,")

        # Negeri fallback: if no Negeri found from Poskod block, try 'Pilihan IKKP Negeri' field
        if not negeri:
            ikkp_m = re.search(
                r"(?i)Pilihan\s+IKKP\s+Negeri[^\n]*\n\s*([A-Z][A-Z\s]{2,30})",
                text,
            )
            if ikkp_m:
                negeri = ikkp_m.group(1).strip().title()  # e.g. "SELANGOR" -> "Selangor"

        # One line: street + Poskod + Bandar + Negeri (spaces between the parts)
        data["maklumat_alamat"] = ", ".join(
            x for x in (
                addr_text,
                " ".join(p for p in (poskod.strip(), bandar.strip()) if p),
                negeri.strip(),
            ) if x
        )
            
        # PIC (Pegawai Dihubungi)
        pic_match = re.search(r"Pegawai Dihubungi\s*\n+(.*?)\n", text, re.IGNORECASE)
        if pic_match:
            data["pegawai_dihubungi"] = pic_match.group(1).strip()
            
        # Contact PIC (No. Telefon)
        contact_match = re.search(r"No\.?\s*(?:,|.)?\s*Telefon.*?\n+([\d\-\+]+)", text, re.IGNORECASE)
        if contact_match:
            data["no_telefon"] = contact_match.group(1).strip()
            
            
        # 2. Extract Table Information
        
        # Since Tesseract reads column-wise sometimes, we extract lists of values
        
        # Plant Registration Numbers (No. Pendaftaran Loji)
        plant_reg_nos = []
        # Look for the section and capture until the next major header (which usually starts with Capital and lowercase, e.g. 'Katogori' or 'Kategori')
        reg_no_section = re.search(r"No\. P[oe]ndaftaran Loji\s*\n(.*?)(?=\n[A-Z][a-z]+ [A-Z]|\n*$)", text, re.DOTALL | re.IGNORECASE)
        if reg_no_section:
            # Split by newline and take any non-empty line as a registration number
            lines = [line.strip() for line in reg_no_section.group(1).split('\n') if line.strip()]
            plant_reg_nos = lines
            
        if not plant_reg_nos:
            # Fallback: extract anything that looks like XX-PMA-XXXX
            plant_reg_nos = re.findall(r"[A-Z]{2,}-PM[A-Z0-9\-]+", text)
            seen = set()
            plant_reg_nos = [x for x in plant_reg_nos if not (x in seen or seen.add(x))]
            
        # Extract Dates (CF Expiry Date)
        dates = re.findall(r"\d{2}/\d{2}/\d{4}", text)
        
        # Fallback Sub Jonis Loji (LIF PENUMPANG, ESKALATOR, etc.)
        sub_jenis = []
        sub_jenis_section = re.search(r"Sub J[oe]nis Loji\s*\n(.*?)(?=\n[A-Z][a-z]+ Loji|\n*$)", text, re.DOTALL | re.IGNORECASE)
        if sub_jenis_section:
            sub_lines = [line.strip() for line in sub_jenis_section.group(1).split('\n') if line.strip() and len(line.strip()) > 3]
            sub_jenis = [line for line in sub_lines if not line.startswith('SLK')]
            
        # Category (Kategori Caj — PDF column AX - … ; allow duplicate A11 / A12 lines)
        # Strategy: prefer the LAST occurrence of "Kategori Caj" in the combined OCR text
        # because image 2 typically has a dedicated clean column listing at the bottom,
        # while image 1 has merged rows that are harder to parse.
        pc = len(plant_reg_nos)
        blob_for_cat = ""

        # Find ALL occurrences of Kategori Caj header; use the last one
        all_kmp = list(re.finditer(r"(?is)Kat[eo]gori\s+Caj(?:\s*-\s*Jenis(?:\s+Loji)?)?", text))
        for kmp in reversed(all_kmp):
            rest = text[kmp.end():]
            # Skip the header line itself
            chop = re.search(r"(?mi)^[^\n]*\r?\n", rest)
            anchor = chop.end() if chop else 0
            slab_tail = rest[anchor:]
            subm = re.search(r"(?mis)(?:^\s*)Sub\s+J[oe]nis\b", slab_tail, re.MULTILINE)
            candidate = (slab_tail[:subm.start()] if subm else slab_tail[:8000]).strip()
            # Use this blob if it contains at least one valid category line
            if re.search(r"(?i)^['\u2018\u2019]?\s*A\s*\d{1,2}\s*-", candidate, re.MULTILINE) or \
               re.search(r"(?i)^['\u2018\u2019]?\s*A[Il]\s*\d\s*-", candidate, re.MULTILINE) or \
               re.search(r"(?i)^AN\s*-", candidate, re.MULTILINE):
                blob_for_cat = candidate
                break

        if not blob_for_cat and all_kmp:
            # Final fallback: use the first match's content
            kmp = all_kmp[0]
            rest = text[kmp.end():]
            chop = re.search(r"(?mi)^[^\n]*\r?\n", rest)
            anchor = chop.end() if chop else 0
            slab_tail = rest[anchor:]
            subm = re.search(r"(?mis)(?:^\s*)Sub\s+J[oe]nis\b", slab_tail, re.MULTILINE)
            blob_for_cat = (slab_tail[:subm.start()] if subm else slab_tail[:8000]).strip()

        category_caj = _extract_kategori_caj_ordered(blob_for_cat, pc)
            
        # Plant Code (Kod Loji or Status Loji)
        kod_loji = []
        kod_section = re.search(r"(Kod Loji|Status Loji)\s*\n(.*?)(?=\nPemunya|\nNo\.? Pendaftaran|\n*$)", text, re.DOTALL | re.IGNORECASE)
        if kod_section:
            kod_lines = [line.strip() for line in kod_section.group(2).split('\n') if line.strip() and len(line.strip()) <= 5]
            kod_loji = kod_lines
            
        # Machine Serial Number (No. Siri Loji)
        # Strategy: The OCR from image 1 produces merged rows like:
        #   "1 SL-PMA-78369 An- LIF LIF PENUMPANG PMA 80530305 17/07/2026 Lihat Loj DISAHKAN"
        # The serial number sits between the plant code (PMA) and the date (DD/MM/YYYY).
        # We parse each merged row to extract it directly.
        no_siri_loji = []

        # Step 1: Parse merged table rows — find serial between PMA and date
        # Match rows that have: row_num SL-PMA-... ... PMA <serial> DD/MM/YYYY
        merged_row_re = re.compile(
            r"(?:^|\n)\s*\d+\s+SL-PMA-\S+.*?\bPMA\b\s+([A-Z0-9][A-Z0-9\-/]{3,14})\s+(\d{2}/\d{2}/\d{4})",
            re.IGNORECASE,
        )
        for mr in merged_row_re.finditer(text):
            serial = mr.group(1).strip()
            # Sanity check: not a plant reg, not a date, not a header keyword
            if (serial not in plant_reg_nos
                    and not re.match(r"^\d{2}/\d{2}/\d{4}$", serial)
                    and not re.match(r"(?i)^(PMA|LIF|ESK|SEMAKAN|LIHAT|DISAHKAN)$", serial)
                    and serial not in no_siri_loji):
                no_siri_loji.append(serial)

        # Step 2: If merged-row approach didn't get enough, try dedicated column listing
        # Look for the LAST "No. Siri Loji" header (prefer clean column from image 2)
        if len(no_siri_loji) < len(plant_reg_nos):
            all_siri_m = list(re.finditer(
                r"(?i)No\.?\s*Siri\s*(?:Loji)?", text
            ))
            for sm in reversed(all_siri_m):
                rest = text[sm.end():]
                # Skip the header line itself
                nl = rest.find("\n")
                rest = rest[nl + 1:] if nl >= 0 else rest
                # Stop before next major column header
                stop = re.search(
                    r"\n(?:Semakan|Tarikh\s+Luput|Kod\s+Loji|Status\s+Loji|Kategori|Sub\s+Jenis|Kadar|Pemunya|No\.\s*Pendaftaran\s+Penghuni)",
                    rest, re.IGNORECASE
                )
                content = rest[:stop.start()] if stop else rest[:800]
                col_lines = [
                    ln.strip() for ln in content.split("\n")
                    if ln.strip() and len(ln.strip()) >= 4
                    and not re.search(r"(?i)SL-PMA|Lihat|DISAHKAN|Semakan|Luput", ln)
                ]
                if col_lines:
                    no_siri_loji = col_lines
                    break

        # Step 3: Last resort fallback using No. Pendaftaran Penghuni column
        if len(no_siri_loji) < len(plant_reg_nos):
            penghuni_m = re.search(
                r"(?mis)No\.?\s*Pendaftaran\s*\n?\s*Penghuni\s*\n(.*?)(?=\n\s*(?:Semakan|Tarikh|Kod|Status|Kategori|Kadar|'|$)|\Z)",
                text, re.DOTALL
            )
            if penghuni_m:
                pg_lines = [
                    ln.strip() for ln in penghuni_m.group(1).split("\n")
                    if ln.strip() and len(ln.strip()) >= 4
                ]
                if pg_lines:
                    no_siri_loji = pg_lines


        kadar_caj = []
        kadar_header_regex = r"(?:Kadar|Rate)\s*\(.*?\)"
        kadar_header_match = re.search(kadar_header_regex, text, re.IGNORECASE)
        if kadar_header_match:
            start_idx = kadar_header_match.end()
            rest_of_text = text[start_idx:]
            stop_match = re.search(r"\n(?:Caj|Semakan|Tarikh|No\.?|Status|[\*])", rest_of_text, re.IGNORECASE)
            kadar_content = rest_of_text[:stop_match.start()] if stop_match else rest_of_text
            kadar_caj = [line.strip() for line in kadar_content.split('\n') if line.strip()]


        # Construct the final rows to send back to Laravel
        tables = []
        rows = []
        
        # Header row
        rows.append(["0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12", "13", "14"])
        
        # Generate a row for each Plant Registration Number found
        num_plants = max(len(plant_reg_nos), 1) if plant_reg_nos else 0
        
        if num_plants > 0:
            for i in range(num_plants):
                plant_reg = plant_reg_nos[i] if i < len(plant_reg_nos) else ""
                expiry = dates[i] if i < len(dates) else ""
                subtype = sub_jenis[i] if i < len(sub_jenis) else ""
                category = category_caj[i] if i < len(category_caj) else ""
                kod = kod_loji[i] if i < len(kod_loji) else ""
                siri = no_siri_loji[i] if i < len(no_siri_loji) else ""
                rate = kadar_caj[i] if i < len(kadar_caj) else ""
                
                row = [
                    str(i + 1),                                        # 0: No
                    data["nama_pemunya"] if i == 0 else "",            # 1: Company
                    data["maklumat_alamat"] if i == 0 else "",         # 2: Address
                    data["no_pendaftaran_pemunya"] if i == 0 else "",  # 3: Registration Number
                    data["pegawai_dihubungi"] if i == 0 else "",       # 4: PIC
                    data["no_telefon"] if i == 0 else "",              # 5: Contact PIC
                    plant_reg,                                         # 6 -> mapped to 8 (Plant Registration Number)
                    category,                                          # 7 -> mapped to 9 (Category)
                    subtype,                                           # 8 -> mapped to 10 (Sub Type)
                    kod,                                               # 9 -> mapped to 11 (Plant Code)
                    siri,                                              # 10 -> mapped to 12 (Machine Serial)
                    expiry,                                            # 11 -> mapped to 13 (CF Expiry Date)
                    rate                                               # 12 -> mapped to 14 (Rate)
                ]
                rows.append(row)
        
        if len(rows) > 1:
            tables.append(rows)
            
        print(json.dumps({"tables": tables}))
        
    except Exception as e:
        print(json.dumps({"error": str(e)}))
        sys.exit(1)

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No file paths provided"}))
        sys.exit(1)
    extract_images(sys.argv[1:])
