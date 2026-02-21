# AI Coding Agent Instructions for image-pipe

## Project Overview

**image-pipe** is a lightweight web-based UI for batch image processing using ImageMagick. It's a single-file PHP application (`index.php`) that provides folder-to-folder image resizing and format conversion on a local server.

**Core Purpose**: Process image batches from an input directory, resize them with configurable constraints, and output in WebP, JPG, or both formats.

---

## Architecture & Key Patterns

### Single-File Design
- **Location**: [index.php](index.php)
- **Structure**: HTML form + PHP backend in one file (monolithic intentional design)
- No separate controllers, models, or routing layers
- State managed via `$_POST` variables and local arrays (`$log`, `$ran`)

### Processing Pipeline
1. **Input Validation**: Check input/output directories, ImageMagick availability
2. **File Discovery**: `glob()` for `*.jpg, *.png` (case-insensitive) in input folder
3. **Batch Processing**: Loop through files, execute `magick` command per file
4. **Format Handling**: 
   - Single format: output as chosen (webp/jpg)
   - "both" mode: generate both `.webp` AND `.jpg` per input file
5. **Error Logging**: Collect all messages in `$log[]` array, display post-submission

### Cross-Platform Considerations
The code handles **Windows vs. Unix** differences:
- Path separators: `DIRECTORY_SEPARATOR`
- ImageMagick detection: `where magick` (Windows) vs. `command -v magick` (Unix)
- Resize operator escaping: `^>` (Windows) vs. `\>` (Unix) — **critical**: `>` must not be shell-interpreted

---

## Critical Developer Workflows

### Running Locally
```bash
cd /var/www/html/image-pipe
php -S localhost:8000
# Visit http://localhost:8000 in browser
```

### Testing ImageMagick Integration
```bash
magick --version
command -v magick  # Unix verification
```

### Manual Batch Test
1. Create test input folder: `/tmp/test-input/`
2. Add `.jpg` or `.png` files
3. Submit form with input/output paths and chosen format
4. Check output folder for `.webp` or `.jpg` files

---

## Conventions & Patterns

### Security
- **Input Sanitization**: `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` via `h()` helper
- **Shell Escaping**: `escapeshellarg()` for all file paths before exec
- **Parameter Bounds**: Hard limits on `maxEdge` (100–20000) and `quality` (1–100)

### Logging Style
- ✅ Success: `"✅ Gefunden: ..."`
- ❌ Error: `"❌ Input-Ordner existiert nicht: ..."`
- ⚠️ Warning: `"⚠️ Keine JPG/PNG Dateien gefunden..."`
- 🎉 Completion: `"🎉 Fertig."`
- Progress (every 25 files): `"… Fortschritt: X/Y"`

### HTML/CSS Patterns
- Minimal inline CSS for portable single-file design
- `system-ui` font stack for cross-platform consistency
- Dark theme for log output (`#0b1020` background, `#e8ecff` text)
- Max-width 900px centered layout

---

## Integration Points & Dependencies

### External Dependencies
- **ImageMagick CLI**: Must be in `PATH`; detect with `command -v magick` or `where magick`
- **PHP**: 7.1+ (uses strict types, spaceship operator not required)
- **File System**: Read access to input folder, write access to output folder

### ImageMagick Command Template
```bash
magick <input> -resize {maxEdge}x{maxEdge}\> -strip -quality {quality} <output>
```
- `-resize {w}x{h}>`: Constrain to max edge without upsizing (platform-specific escaping required)
- `-strip`: Remove metadata/profiles
- `-quality`: JPEG/WebP compression (1–100)

### No External API Calls
Process runs entirely locally; no remote dependencies.

---

## Common Maintenance Tasks

### Adding New Image Format Support
1. Add option to `<select name="format">` in HTML
2. Add new condition block in batch loop: `if ($format === 'new' || $format === 'both')`
3. Build command with appropriate flags (e.g., `-auto-orient` for JPEG)
4. Update output file extension

### Improving Error Messages
Errors currently show raw ImageMagick stderr. To enhance:
- Parse `$oJ` or `$oW` output arrays to extract key error phrases
- Provide suggestions (e.g., "File corrupted?" or "Out of disk space?")

### Performance Optimization
Current approach is sequential per-file. Large batches may timeout:
- Consider progress reporting via AJAX/fetch for long operations
- Batch multiple files per `magick` call (if format consistent)

---

## Language & Localization Notes

- **Current Language**: German (UI labels, error messages, log output)
- File paths in output use server OS conventions
- Keep error messages bilingual or review German UX if changing

---

## Common Gotchas

1. **ImageMagick Not in PATH**: Script detects but check system `PATH` environment variable
2. **Resize `>` Operator**: Different escaping per OS; must preserve this logic
3. **File Permissions**: Output folder needs write access; `mkdir(..., 0777)` may need OS adjustment
4. **Character Encoding**: Form input/output handled via UTF-8; maintain in any new features
5. **Form Resubmit**: No CSRF token; suitable for local/trusted network only
