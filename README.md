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
- **Database**: MariaDB 11.8 or newer. The plugin stores vectors and compares them in the database (photo embedding, tags indexation, smart and closed tags modes).
  - On any other database (MySQL, an older MariaDB), the plugin does not install and does not activate, and it says why.
  - A gallery that updates the plugin on such a database sees the same message in the administration. Once the database is updated, deactivate and reactivate the plugin.
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

