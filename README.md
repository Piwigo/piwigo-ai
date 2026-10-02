# Piwigo AI
> **Beta** — This plugin is currently in beta. You may encounter bugs or unexpected behavior.

Transform your Piwigo gallery into an AI-powered smart platform!

## Summary
- [Prerequisites](#prerequisites)
- [Installation](#installation)
  - [For Users](#for-users)
  - [For Developers](#for-developers)
- [Features](#features)
- [Dev](#dev)
- [License](#license)

## Prerequisites
- **PHP**: 8.4 minimum
- **Database**: the plugin works on any database Piwigo supports. The vector features (photo embedding, tags indexation, smart and closed tags modes) depend on it:
  - **MariaDB 11.7+** (11.8 recommended): every feature.
  - **MySQL 9.0+**: vectors are stored and tags can be indexed, but the smart and closed tags modes are unavailable (no vector distance function in the Community edition).
  - **Older versions**: degraded mode, with descriptions, tags (open mode) and OCR only. The overview page tells when the database is not compatible, and can check it again.
- **Public galleries** (the AI server sends results back): PHP `post_max_size` of at least 8 MB, for the batches of tag embeddings. Below, they come by polling, more slowly.

## Installation
### For Users 
Coming..

### For Developers
1. **Clone the Repository**:
   - Clone the Piwigo AI repository to your local machine using:
     ```git clone https://github.com/Piwigo/piwigo-ai.git```
2. **Development and Contributions**:
   - Make your changes or improvements to the code.
   - See the [Dev](#dev) section for building CSS.
   - Test your changes thoroughly.
   - Feel free to submit a pull request if you wish to contribute your changes back to the project.

## Features
- **Descriptions**: a description of each photo, shown after the regular one if enabled (with an optional prefix).
- **Tags**, in one of three modes (Configuration > Tags):
  - **Open**: the AI creates its own tags.
  - **Smart**: the AI prefers the indexed tags of the gallery, and creates a tag only when it fits the photo better.
  - **Closed**: the AI only uses the indexed tags of the gallery, it never creates one.
- **OCR**: the text found in each photo.
- **Embedding**: a vector of each photo and of its tags, used to choose the tags, and later for similar photos and natural-language search.
- **Tags indexation**: from the overview, the tags without vector are sent to the AI server in one go; a card follows the progress live. A renamed tag loses its vector until the next indexation.
- **Where to analyze**: in the uploader (per upload options) and in the batch manager (Piwigo AI action).
- **Private or public gallery**: a private gallery uploads its photos and polls the results; a public one lets the AI server fetch the photos and send the results back, with polling as a fallback.

## Dev
From the plugin directory (`plugins/piwigo_ai`):

> We are experimenting with Tailwind CSS in this plugin as part of an effort to make Piwigo plugin development a more enjoyable experience for developers.

Build the Tailwind CSS for production (commit `css/output.css` with the templates that need it):
```
npm run css:build
or
npx @tailwindcss/cli -i css/input.css -o css/output.css --minify
```

Watch for CSS changes during development:
```
npm run css:watch
or
npx @tailwindcss/cli -i css/input.css -o css/output.css --minify --watch
```

Database changes go in numbered migrations (`migrations/N-database.php`), applied once each when the plugin is installed or updated. Each one checks the schema before changing it, so it can run again after a partial failure.

