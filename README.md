# 🖼️ image-pipe

Eine leichtgewichtige, webbasierte UI für die Batch-Bildverarbeitung mit ImageMagick. Single-File PHP-Anwendung für schnelles Resizing und Format-Konvertierung auf lokalen Servern.

## 📋 Übersicht

**image-pipe** ist eine minimalistische Web-Oberfläche, die ImageMagick-Bildverarbeitung über einen Browser zugänglich macht. Perfekt für lokale Entwicklungsumgebungen, wo schnelle Batch-Konvertierungen von Bildern benötigt werden – ohne komplexe Setups oder externe Abhängigkeiten.

## ✨ Features

### 📤 Datei-Upload
- **Multi-File-Upload**: Mehrere Bilder gleichzeitig hochladen
- **Format-Support**: JPG, PNG, WebP
- **Validierung**: MIME-Type-Prüfung und Größenlimit (50 MB pro Datei)
- **Drag & Drop**: Browser-native Dateiauswahl

### ⚙️ Bildverarbeitung
- **Zwei Resize-Modi**:
  - **Max Edge**: Skaliert Bilder proportional auf maximale Kantenlänge
  - **Fixed Size**: Croppt Bilder auf exakte Abmessungen mit einstellbarer Gravity-Position (9 Positionen)
- **Format-Konvertierung**: Ausgabe als WebP, JPG oder beides
- **Qualitätseinstellungen**: Kompressionsqualität von 1-100 einstellbar
- **Batch-Verarbeitung**: Verarbeitet alle Bilder im Input-Ordner auf einmal
- **Fortschritts-Anzeige**: Progress-Updates alle 25 Dateien

### 📁 Dateimanagement
- **Input/Output-Ordner**: Separate Ordner für Quell- und Zieldateien
- **Datei-Übersicht**: Liste aller Dateien mit Anzahl und Gesamtgröße
- **Umbenennen**: Inline-Bearbeitung von Dateinamen im Input-Ordner
- **Ordner löschen**: Ein-Klick-Löschung aller Dateien (mit Bestätigung)
- **Größenangaben**: Anzeige des Speicherverbrauchs in Bytes/KB/MB/GB

### 💾 Download-Funktionen
- **Einzelner Download**: Jede Datei einzeln herunterladen
- **ZIP-Download**: Kompletten Output-Ordner als Archiv herunterladen

### 📊 Einsparungsanalyse
- **Speicher-Vergleich**: Automatische Berechnung der Größenersparnis
- **Prozentuale Darstellung**: Zeigt Kompressionsrate in %
- **Farbcodierung**: Grün für Einsparungen, Rot bei Größenzunahme

### 🔧 Persistenz & Komfort
- **Cookie-basierte Settings**: Alle Verarbeitungseinstellungen werden gespeichert (1 Jahr)
- **Intelligente UI**: Batch-Verarbeitung wird nur bei vorhandenen Input-Dateien angezeigt
- **Responsive Design**: Funktioniert auf Desktop und mobilen Geräten

## 🚀 Installation

### Voraussetzungen

- **PHP**: Version 7.1 oder höher (mit `strict_types` Support)
- **ImageMagick**: Installiert und in `PATH` verfügbar
- **Webserver**: Apache, Nginx oder PHP Development Server

### ImageMagick installieren

**Ubuntu/Debian:**
```bash
sudo apt install imagemagick
```

**macOS:**
```bash
brew install imagemagick
```

**Windows:**
- Download von [imagemagick.org](https://imagemagick.org/script/download.php)
- Bei Installation "Add to PATH" aktivieren

### Projekt einrichten

1. **Repository klonen:**
```bash
git clone https://github.com/web-mex/image-pipe.git
cd image-pipe
```

2. **Ordner erstellen** (werden automatisch erstellt, aber optional):
```bash
mkdir input output
chmod 777 input output  # Bei Bedarf Schreibrechte setzen
```

3. **Server starten:**
```bash
php -S localhost:8000
```

4. **Browser öffnen:**
```
http://localhost:8000
```

## 📖 Verwendung

### 1. Bilder hochladen
- Klicke auf "Bilder auswählen" im Upload-Bereich
- Wähle eine oder mehrere Bilddateien (JPG, PNG, WebP)
- Klicke "Hochladen" – Dateien werden in den Input-Ordner kopiert

### 2. Batch-Verarbeitung konfigurieren

**Resize-Modus wählen:**
- **Maximale Kantenlänge**: Behält Seitenverhältnis bei, begrenzt größte Kante (z.B. 1600px)
- **Feste Größe**: Croppt Bild auf exakte Abmessungen (z.B. 800×600px)

**Crop-Position** (nur bei "Feste Größe"):
- Wähle aus 9 Positionen: Mitte, Oben, Unten, Links, Rechts, alle Ecken
- Nützlich z.B. für Copyright-Schutz (Unten-Links)

**Qualität & Format:**
- Qualität: 1-100 (Standard: 85, empfohlen für Web: 75-85)
- Format: WebP (modern, klein), JPG (kompatibel) oder beides

### 3. Verarbeitung starten
- Klicke "Start" – alle Bilder im Input-Ordner werden verarbeitet
- Log zeigt Fortschritt und Fehler an
- Verarbeitete Bilder erscheinen im Output-Ordner

### 4. Ergebnisse herunterladen
- **Einzeldownload**: Klicke ⬇️ neben jeder Datei
- **ZIP-Download**: Klicke "⬇ ZIP" für kompletten Output-Ordner

### 5. Aufräumen
- **Input löschen**: ✕-Button beim Input-Ordner
- **Output löschen**: ✕-Button beim Output-Ordner

## 🛠️ Technische Details

### Architektur
- **Single-File Design**: Komplette Anwendung in `index.php` (ca. 500 Zeilen)
- **Keine Datenbank**: Alles dateibasiert
- **Keine externen Libraries**: Pure PHP + HTML + CSS + JavaScript

### Sicherheit
- **Input-Sanitization**: `htmlspecialchars()` für alle Ausgaben
- **Shell-Escaping**: `escapeshellarg()` für alle Dateipfade
- **MIME-Type-Prüfung**: Verhindert Upload nicht-unterstützter Formate
- **Path-Sanitization**: `basename()` verhindert Directory Traversal
- **Parameter-Bounds**: Hard Limits für `maxEdge` (100-20000) und `quality` (1-100)

### Cross-Platform Support
- **Path-Separators**: `DIRECTORY_SEPARATOR` für Windows/Unix
- **ImageMagick-Erkennung**: Unterstützt `magick` (v7) und `convert` (v6)
- **Resize-Operator-Escaping**: `^>` (Windows) vs. `\>` (Unix)

### ImageMagick-Befehle

**Max Edge Modus:**
```bash
convert input.jpg -resize 1600x1600\> -strip -quality 85 output.webp
```

**Fixed Size Modus:**
```bash
convert input.jpg -resize 800x600^ -gravity center -extent 800x600 -strip -quality 85 output.jpg
```

**Parameter:**
- `-resize WxH\>`: Verkleinert auf max W×H, behält Aspect Ratio, vergrößert nicht
- `-resize WxH^`: Vergrößert/verkleinert, füllt mindestens W×H (für Cropping)
- `-gravity POS`: Crop-Position (center, north, south, east, west, northeast, etc.)
- `-extent WxH`: Croppt auf exakt W×H
- `-strip`: Entfernt Metadaten/EXIF
- `-quality Q`: Kompressionsqualität (1-100)

## 📝 Cookie-Settings

Folgende Einstellungen werden im Browser gespeichert (1 Jahr):
- `resizeMode`: `maxEdge` oder `fixedSize`
- `maxEdge`: Maximale Kantenlänge (100-20000)
- `fixedWidth`: Feste Breite für Crop-Modus
- `fixedHeight`: Feste Höhe für Crop-Modus
- `cropGravity`: Crop-Position (`center`, `north`, `south`, etc.)
- `quality`: Kompressionsqualität (1-100)
- `format`: Ausgabeformat (`webp`, `jpg`, `both`)

## 🐛 Troubleshooting

### ImageMagick nicht gefunden
**Problem:** Fehlermeldung "ImageMagick nicht gefunden"

**Lösung:**
```bash
# Installationsstatus prüfen
command -v magick    # v7+
command -v convert   # v6

# PATH prüfen
echo $PATH

# Bei Bedarf zu PATH hinzufügen (Linux/Mac)
export PATH=$PATH:/usr/local/bin
```

### Datei-Upload schlägt fehl
**Problem:** "Upload fehlgeschlagen" im Log

**Lösung:**
- Prüfe Schreibrechte auf `input/` Ordner: `chmod 777 input`
- Prüfe PHP `upload_max_filesize` und `post_max_size` in `php.ini`
- Prüfe Server-Logs: `tail -f /var/log/php_errors.log`

### Bilder werden nicht verarbeitet
**Problem:** Batch-Verarbeitung startet nicht

**Lösung:**
- Mindestens 1 Bild muss im Input-Ordner sein
- Unterstützte Formate: JPG, PNG (WebP im Input erfordert ImageMagick-Support)
- Prüfe ImageMagick-Logs im Verarbeitungs-Log-Bereich

### ZIP-Download funktioniert nicht
**Problem:** ZIP-Datei leer oder Fehler

**Lösung:**
- Prüfe ob PHP-Extension `zip` installiert ist: `php -m | grep zip`
- Falls nicht: `sudo apt install php-zip` (Ubuntu/Debian)

## 🔐 Sicherheitshinweise

**⚠️ Nur für lokale/vertrauenswürdige Netzwerke!**

- Kein CSRF-Schutz implementiert
- Keine Benutzer-Authentifizierung
- Datei-Uploads ohne zusätzliche Malware-Scans
- Für Produktionsumgebungen zusätzliche Security-Layer empfohlen

## 📄 Lizenz

Dieses Projekt ist Open Source. Siehe LICENSE-Datei für Details.

## 🤝 Beitragen

Contributions sind willkommen! Bitte:
1. Fork das Repository
2. Erstelle einen Feature-Branch (`git checkout -b feature/AmazingFeature`)
3. Committe deine Änderungen (`git commit -m 'Add some AmazingFeature'`)
4. Push zum Branch (`git push origin feature/AmazingFeature`)
5. Öffne einen Pull Request

## 📧 Support

Bei Fragen oder Problemen öffne ein Issue auf GitHub.

---

**Made with ❤️ for fast local image processing**
