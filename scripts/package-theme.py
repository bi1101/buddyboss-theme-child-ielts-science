#!/usr/bin/env python3
"""Create and validate a deterministic installable WordPress theme ZIP."""
from pathlib import Path
import sys
import zipfile

ROOT = Path(__file__).resolve().parents[1]
DIST = ROOT / "dist"
# The checkout directory can differ from the WordPress theme slug.
THEME = "buddyboss-theme-child"
ARCHIVE = DIST / f"{THEME}.zip"
EXCLUDED_DIRS = {".git", ".github", "node_modules", "tests", "test", "dist", "scripts"}
EXCLUDED_SUFFIXES = {".map"}
EXCLUDED_FILES = {".gitignore", "README.md", "package.json", "package-lock.json", "webpack.config.js"}


def files_to_package():
    for path in sorted(ROOT.rglob("*")):
        if not path.is_file():
            continue
        relative = path.relative_to(ROOT)
        if any(part in EXCLUDED_DIRS for part in relative.parts):
            continue
        if path.suffix in EXCLUDED_SUFFIXES or path.name in EXCLUDED_FILES:
            continue
        yield path, Path(THEME) / relative


def validate():
    if not ARCHIVE.is_file():
        raise SystemExit(f"Missing archive: {ARCHIVE}")
    with zipfile.ZipFile(ARCHIVE) as archive:
        names = set(archive.namelist())
        prefix = f"{THEME}/"
        required = {f"{prefix}style.css"}
        if not required <= names:
            raise SystemExit(f"Missing required files: {sorted(required - names)}")
        if any(not name.startswith(prefix) for name in names):
            raise SystemExit("Archive contains files outside its installable top-level directory")
        if any(name.endswith(".map") or "/.git/" in name for name in names):
            raise SystemExit("Archive contains excluded development files")
        style = archive.read(f"{prefix}style.css").decode("utf-8", errors="replace")
        if "Theme Name:" not in style or (THEME == "buddyboss-theme-child" and "Template:" not in style):
            raise SystemExit("style.css is missing WordPress theme metadata")
    print(f"Validated {ARCHIVE} ({len(names)} files)")


def package():
    DIST.mkdir(exist_ok=True)
    if ARCHIVE.exists():
        ARCHIVE.unlink()
    with zipfile.ZipFile(ARCHIVE, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for path, archive_name in files_to_package():
            info = zipfile.ZipInfo(str(archive_name).replace("\\", "/"), (1980, 1, 1, 0, 0, 0))
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = 0o644 << 16
            archive.writestr(info, path.read_bytes())
    validate()


if __name__ == "__main__":
    if len(sys.argv) != 2 or sys.argv[1] not in {"package", "validate"}:
        raise SystemExit("Usage: package-theme.py package|validate")
    package() if sys.argv[1] == "package" else validate()
