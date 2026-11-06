#!/usr/bin/env python3
"""Create an installable WordPress theme ZIP without third-party packages."""

from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile

ROOT = Path(__file__).resolve().parents[1]
THEME = ROOT / "wp-content" / "themes" / "andalan-mutu-energi"
DESTINATION = ROOT / "andalan-mutu-energi.zip"
EXCLUDED = {".DS_Store", "Thumbs.db"}

if not THEME.is_dir():
    raise SystemExit(f"Theme directory not found: {THEME}")

DESTINATION.parent.mkdir(parents=True, exist_ok=True)
with ZipFile(DESTINATION, "w", ZIP_DEFLATED, compresslevel=9) as archive:
    for source in sorted(THEME.rglob("*")):
        if source.is_file() and source.name not in EXCLUDED:
            relative = source.relative_to(THEME)
            archive.write(source, Path("andalan-mutu-energi") / relative)

print(f"Created {DESTINATION} ({DESTINATION.stat().st_size:,} bytes)")
