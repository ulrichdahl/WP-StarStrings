# Star Citizen Localization

A WordPress plugin for building, managing, and downloading customized `global.ini` localization files for **Star Citizen**.

## What it does

This plugin helps you:

- upload and manage the localization source files used to generate Star Citizen language files
- define a custom component naming format
- sort and rename vehicles before export
- generate a ready-to-use `global.ini` file
- set an optional version message shown in the game’s main menu
- keep your saved setup for future patches

## Main features

### 1. Component format builder
You can build a custom naming format by combining tokens such as:

- type
- classification
- size
- grade
- name
- separators like space, hyphen, underscore, and dot

This makes it easy to generate consistent component names.

### 2. Vehicle sorting and renaming
The plugin lets you:

- search vehicles
- move them between available and selected lists
- sort them manually
- group nested vehicles
- edit displayed vehicle names

### 3. File generation
When everything is configured, the plugin generates a customized `global.ini` file for download.

### 4. Admin settings
In the WordPress admin area, you can upload and manage:

- `global.ini`
- `components.ini`
- `vehicles.ini`
- `contracts.ini`
- `extras.ini`

You can also set:

- the latest `global.ini` version
- a version message for the main menu

### 5. Frontend shortcode
The plugin provides a frontend interface for visitors or editors to use the localization tool.

## How it works

1. Upload the required INI files in the WordPress admin.
2. Open the frontend localization tool.
3. Choose your component format.
4. Select and sort the vehicles you want.
5. Download the generated `global.ini`.
6. Install the file in your Star Citizen `LIVE` folder as described in the plugin interface.

## Requirements

- WordPress
- PHP
- The required Star Citizen localization files uploaded in the plugin settings

## Installation

1. Upload the plugin to your WordPress installation.
2. Activate it from the Plugins page.
3. Open the localization settings in the WordPress admin.
4. Upload the required INI files.
5. Use the frontend tool to generate your localization file.

## Video guide

Watch the demo video here:

https://youtu.be/s29tpiQWya8?si=CW6c6cC_zSrGmsxY

## Notes

- The plugin creates its own upload directory inside WordPress uploads.
- If the required files are missing, the frontend will show a message instead of the tool.
- The generated output is meant to be used with Star Citizen’s localization setup.

## License

GPL-2.0 or later