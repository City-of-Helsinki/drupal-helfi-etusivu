import path from 'path';
import { globSync } from 'glob';
import { buildAll, watchAndBuild } from '@hdbt/theme-builder/builder';

const __dirname = path.resolve();
const isDev = process.argv.includes('--dev');
const isWatch = process.argv.includes('--watch');
const watchPaths = ['src/js', 'src/scss'];
const outDir = path.resolve(__dirname, 'dist');

// Vanilla JS files.
const jsFiles = globSync('./src/js/**/*.js')
  .reduce((acc, file) => ({
    ...acc, [path.parse(file).name]: file
  }),
{});

// SCSS files.
const styles = [
  ['src/scss/styles.scss', 'css/styles.min.css'],
];

// Static files.
const staticFiles = [
  ['node_modules/@drupal/autocomplete/dist/a11y.autocomplete.min.js', `${outDir}/js/a11y-autocomplete.min.js`],
];

// Builder configurations.
const entries = { ...jsFiles };
const jsConfig = { entries, isDev, outDir };
const cssConfig   = { styles, isDev, outDir };
const buildArguments   = { outDir, staticFiles, jsConfig, cssConfig };

if (isWatch) {
  watchAndBuild({
    buildArguments,
    watchPaths,
  });
} else {
  buildAll(buildArguments).catch((e) => {
    console.error('❌ Build failed:', e);
    process.exit(1);
  });
}
