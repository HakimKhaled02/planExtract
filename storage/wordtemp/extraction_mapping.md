# PDF to Database Extraction Mapping

This document serves as the reference guide for extracting data from the `project_template.pdf` and mapping it into the 17-column database schema.

## Source Document
**File**: `storage/wordtemp/project_template.pdf`

## Data Mapping Schema

The system uses a standard 17-column layout. The data from the PDF should be mapped as follows:

| Order | Standard Column Name | PDF Source Field / Section | Notes |
| :---: | :--- | :--- | :--- |
| **0** | No | *Incremental Row Number* | Auto-generated/extracted row index. |
| **1** | Company | `nama pemunya` | Extracted from the header/general info section. |
| **2** | Address | `maklumat alamat` | Extracted from the header/general info section. |
| **3** | Registration Number | `no pendaftaran pemunya` | Extracted from the header/general info section. |
| **4** | PIC | `pegawai dihubungi` | Extracted from the header/general info section. |
| **5** | Contact PIC | `no telefon` | Extracted from the header/general info section. |
| **6** | Inspection Date | *N/A (Manual Entry)* | To be filled manually via the system UI. |
| **7** | Time | *N/A (Manual Entry)* | To be filled manually via the system UI. |
| **8** | Plant Registration Number| `senarai loji untuk pemeriksaan` | Extracted from the plant list table section. |
| **9** | Category | `senarai loji untuk pemeriksaan` | Extracted from the plant list table section. |
| **10** | Sub Type | `senarai loji untuk pemeriksaan` | Extracted from the plant list table section. |
| **11** | Plant Code | `senarai loji untuk pemeriksaan` | Extracted from the plant list table section. |
| **12** | Machine Serial Number | `senarai loji untuk pemeriksaan` | Extracted from the plant list table section. |
| **13** | CF Expiry Date | `senarai loji untuk pemeriksaan` | Extracted from the plant list table section. |
| **14** | Rate | `kadar` | Extracted from the specific rate column/field. |
| **15** | Status | *N/A (Manual Entry)* | System default or manual entry. |
| **16** | Remark | *N/A (Manual Entry)* | System default or manual entry. |

### Implementation Notes for `extract.py`

When updating the Python extraction script:
1. **Header Extraction**: The script needs to parse key-value pairs at the top of the PDF for fields like `nama pemunya` and `maklumat alamat`. This data will be duplicated across all rows generated for this project.
2. **Table Extraction**: The section `senarai loji untuk pemeriksaan` acts as the primary data table. Each row in this section will generate a new row in our database (repeating the header information).
3. **Regex/Keyword Targeting**: The Python script should specifically look for the exact Malay keywords provided above to identify the bounding boxes or text streams containing the values.
