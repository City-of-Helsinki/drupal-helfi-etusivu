# Helfi etusivu news search

Helfi etusivu news search is a React app used as a UI for the search functionalities in drupal-helfi-etusivu instances news archive.

To make most of this app, make sure you have installed the drupal-helfi-etusivu project and have set it up properly.

## Getting started

First, copy .env.example to .env and add missing variables
```console
foo@bar:~$ cp .env.example .env
```
Start up the project
```console
foo@bar:~$ nvm use
foo@bar:~$ npm i
foo@bar:~$ npm start
```

Navigate to `http://localhost:3000` to view the app.

To run the tests:
```console
foo@bar:~$ npm t
```

## Useful commands

### Development

`npm start`
Run the app in development mode.\
Open [http://localhost:3000](http://localhost:3000) to view it in your browser.
The page will reload when you make changes.\

### Deployment 

`npm run build`
Builds the app and copies bundle to `assets/main.js`

`npm run analyze`
Bundle size too big? Run this to see what is bloating it.

### Testing 

`npm t`
Runs runs changed tests and opens a dialog for further options.

`npm t -- --coverage`
To see test coverage stats.

### Ejecting from the create-scripts

`npm run eject`
Access configuration and boilerplate offered by react-scripts directly.
Don't run this unless you know what you're doing.

### Folder structure

As React has no standardized folder structure, and this app is pretty contained let's try to keep the folder structure simple. Group files to top-level folders by feature.

### SCSS

Styles reside in [HDBT](https://github.com/City-of-Helsinki/drupal-hdbt) theme.

### Coding style

We have a pre-commit hook that runs through prettier.

---

This project was bootstrapped with [Create React App](https://github.com/facebook/create-react-app).
