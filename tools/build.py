"""Build a reproducible Joomla installer from manifest-declared files only."""
from pathlib import Path
from zipfile import ZipFile, ZipInfo, ZIP_DEFLATED
import hashlib
import xml.etree.ElementTree as ET

root = Path(__file__).resolve().parents[1]
manifest = root / "rpcustomscrollbar.xml"
xml = ET.parse(manifest).getroot()
version = xml.findtext("version")
paths = {manifest}
for entry in xml.find("files"):
    path = root / entry.text
    if entry.tag == "folder":
        paths.update(p for p in path.rglob("*") if p.is_file())
    else:
        paths.add(path)
dist = root / "dist"
dist.mkdir(exist_ok=True)
archive = dist / f"rp-custom-scrollbar-joomla6-{version}.zip"
with ZipFile(archive, "w", ZIP_DEFLATED) as bundle:
    for path in sorted(paths):
        info = ZipInfo(path.relative_to(root).as_posix(), (2026, 11, 16, 0, 0, 0))
        info.compress_type = ZIP_DEFLATED
        info.external_attr = 0o100644 << 16
        bundle.writestr(info, path.read_bytes())
with ZipFile(archive) as bundle:
    if bundle.testzip() is not None:
        raise RuntimeError("Invalid ZIP")
checksum = hashlib.sha256(archive.read_bytes()).hexdigest()
(dist / "SHA256SUMS.txt").write_text(f"{checksum}  {archive.name}\n")
print(f"{archive.name}: {checksum}")
