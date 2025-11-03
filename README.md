# 📘 Amar Dictionary - English to Bangla


[![Live Demo](https://img.shields.io/badge/Live-Demo-blue?style=for-the-badge)](https://dictionary.zone.id)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)
[![Version](https://img.shields.io/badge/Version-1.0.0-orange?style=for-the-badge)]()

**Amar Dictionary** is a modern bilingual dictionary web app designed for quick English–Bangla word lookup with detailed meanings, pronunciation, examples, synonyms, antonyms, images, and more — all powered by a custom API built by [@harunabdullahrakin](https://github.com/harunabdullahrakin).

🔗 **Live App:** [https://dictionary.zone.id](https://dictionary.zone.id)
🧠 **API Endpoint:** [https://dictionary.zone.id/api.php?word=love](https://dictionary.zone.id/api.php?word=love)

---

## 🚀 Overview

Amar Dictionary combines multiple dictionary and translation APIs with an external database to deliver the **most complete Bangla and English meaning data** possible.
The backend intelligently merges:

* **Bangla translation**
* **English phonetics & pronunciation**
* **Parts of speech & definitions**
* **Example sentences**
* **Synonyms / antonyms**
* **Images**
* **Urban and informal meanings**

The result is a **powerful dictionary API** that can be used directly by developers or through the **Amar Dictionary web interface** (`index.html`).

---

## ✨ Features

✅ **Modern UI** — built with responsive HTML, CSS (Tailwind + FontAwesome), and JavaScript.

✅ **Fast API** — single endpoint returning structured JSON with everything you need.

✅ **Bangla + English support** — translations, phonetics, and definitions in both languages.

✅ **Pronunciation audio** — UK and US voice files from verified sources.

✅ **Images** — dynamically fetched from public image APIs (Pinterest/CDN).

✅ **PWA Ready** — installable on devices (`manifest.json`, `sw.js`).

✅ **Offline-friendly** — cached with service worker support.

✅ **Custom-built backend** — fully written in PHP by the project author.

✅ **Lightweight** — no database server required; external APIs + JSON data handle it all.

---

## 🧩 Example API Request & Response

**Request:**

```
https://dictionary.zone.id/api.php?word=love
```

**Response (truncated for readability):**

```json
{
  "word": "love",
  "bangla_translation": {
    "translated": "ভালোবাসা",
    "pronunciation": "lʌv",
    "pron_audio": {
      "uk": "https://support.igofun.mobi/hi-translate/pron/uk_us_audio/love_uk_male.mp3",
      "us": "https://support.igofun.mobi/hi-translate/pron/uk_us_audio/love_us_male.mp3"
    }
  },
  "english_pronunciation": {
    "phonetic": "/lʊv/",
    "phonetics": [
      {"text": "/lʌv/", "audio": "https://api.dictionaryapi.dev/media/pronunciations/en/love-uk.mp3"},
      {"text": "/lʌv/", "audio": "https://api.dictionaryapi.dev/media/pronunciations/en/love-us.mp3"}
    ]
  },
  "parts_of_speech": [
    {
      "partOfSpeech": "noun",
      "definitions": ["Strong affection.", "A person who is the object of romantic feelings; a darling."],
      "examples": ["Hello love, how can I help you?"]
    },
    {
      "partOfSpeech": "verb",
      "definitions": ["To have a strong affection for (someone or something)."],
      "examples": ["I love my spouse. I love you!"]
    }
  ],
  "synonyms": ["darling", "sweetheart", "romance", "adore", "cherish"],
  "antonyms": ["hate", "malice", "spite", "despise"],
  "images": [
    "https://i.pinimg.com/originals/1b/58/13/1b58133a9f841c743676c9539b3605f7.jpg",
    "https://i.pinimg.com/originals/9a/75/12/9a75123f92c4cbe57a9a779940fbbcd6.jpg"
  ],
  "sources": {
    "anomaki_api": true,
    "dictionaryapi": true,
    "urban": true,
    "pinsearch": true,
    "e2b_json": true
  }
}
```

---

## 🧠 Data Sources

Amar Dictionary aggregates data from multiple open and self-built APIs:

| Source                   | Description                                      |
| ------------------------ | ------------------------------------------------ |
| **Anomaki API**          | Core English–Bangla translation source           |
| **DictionaryAPI.dev**    | Provides English definitions & phonetics         |
| **Urban Dictionary API** | Informal / slang meanings                        |
| **PinSearch**            | Image search for visual word representations     |
| **E2B JSON Database**    | External JSON-based Bangla word database         |
| **Custom Database**      | Locally stored entries for rare or missing words |

---

## 🧰 Tech Stack

**Frontend:**

* HTML5 + Tailwind CSS
* JavaScript (Dynamic word rendering)
* FontAwesome icons
* Service Worker (`sw.js`) + PWA (`manifest.json`)

**Backend:**

* PHP (`api.php`)
* External API integrations
* JSON-based data handling

**Hosting:**

* Hosted on [AlwaysData](https://www.alwaysdata.com)
* Live at [dictionary.zone.id](https://dictionary.zone.id)

---

## ⚙️ File Structure

```
Amar-Dictionary/
├─ index.html           # Main user interface
├─ api.php              # Main API endpoint (PHP)
├─ manifest.json        # PWA configuration
├─ sw.js                # Service Worker
├─ wrapper.js           # Dynamic JS renderer
├─ db.json              # Local data (backup / extra words)
├─ favicon-96x96.png
├─ apple-touch-icon.png
├─ web-app-manifest-192x192.png
├─ web-app-manifest-512x512.png
└─ /assets              # Fonts, icons, styles
```

---

## 💡 How It Works

1. The user searches a word in the web interface.
2. The frontend calls the main API:
   `https://dictionary.zone.id/api.php?word=love`
3. The backend merges results from various sources (Bangla, English, Urban, Images).
4. The response is returned as JSON.
5. The frontend dynamically displays sections like **Bangla Meaning**, **Parts of Speech**, **Examples**, **Synonyms**, **Images**, etc.

---

## 🔌 API Usage for Developers

**JavaScript Example:**

```js
fetch("https://dictionary.zone.id/api.php?word=love")
  .then(res => res.json())
  .then(data => console.log(data));
```

**Response Fields:**

| Key                     | Description                                          |
| ----------------------- | ---------------------------------------------------- |
| `word`                  | The searched English word                            |
| `bangla_translation`    | Primary Bangla translation and pronunciation         |
| `english_pronunciation` | English phonetic symbols and audio links             |
| `parts_of_speech`       | Grammatical categories with definitions and examples |
| `synonyms` / `antonyms` | Related and opposite words                           |
| `images`                | Relevant pictures for visualization                  |
| `urban_meaning`         | Informal or slang definition                         |
| `sources`               | Boolean map of which APIs were used                  |

---

## 📦 Installation (For Developers)

```bash
git clone https://github.com/harunabdullahrakin/Amar-Dictionary.git
cd Amar-Dictionary
```

Then deploy to any PHP-compatible server.
Example for AlwaysData:

* Upload files via FTP
* Set your web root to the `Amar-Dictionary` directory
* Done! Access it from your domain

---

## 🧑‍💻 Author

**Developed by:**
👤 [Harun Abdullah Rakin](https://github.com/harunabdullahrakin)


---

## 🪪 License

This project is licensed under the **MIT License** — feel free to use and extend it for educational or personal projects.
Attribution is appreciated.

---

> 💬 “Amar Dictionary — making word meanings beautifully bilingual.”
