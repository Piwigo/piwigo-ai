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
- **Database** (one of the following):
  - MariaDB: 11.7 minimum, 11.8 recommended
  - MySQL: 9.0 minimum

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
- **AI-Powered Tagging**: Automatically generate tags for your photos using AI.
- **Smart Descriptions**: Let AI generate descriptions for your images.
- **Optical Character Recognition (OCR)**: Extract text from your images automatically, making your photos searchable by their textual content.
- **Easy Integration**: Seamlessly integrates with Piwigo, allowing for a quick and hassle-free setup.

## Dev
From the plugin directory (`plugins/piwigo_ai`):

> We are experimenting with Tailwind CSS in this plugin as part of an effort to make Piwigo plugin development a more enjoyable experience for developers.

Build the Tailwind CSS for production:
```
npm run css:build
or
npx @tailwindcss/cli -i css/input.css -o css/output.css --minify
```

Watch for CSS changes during development:
```
pnpm run css:watch
or
npx @tailwindcss/cli -i css/input.css -o css/output.css --minify --watch
```

