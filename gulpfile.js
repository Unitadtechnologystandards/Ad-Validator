var gulp          = require('gulp');
var concat        = require('gulp-concat');
var $             = require('gulp-load-plugins')();
var autoprefixer  = require('autoprefixer');

var resources = {
  fonts: [
    'node_modules/font-awesome/fonts/fontawesome-webfont.ttf',
    'node_modules/font-awesome/fonts/fontawesome-webfont.woff',
    'node_modules/font-awesome/fonts/fontawesome-webfont.woff2',
  ],
  scripts: [
    'node_modules/jquery/dist/jquery.min.js',
    'node_modules/what-input/dist/what-input.min.js',
    'node_modules/foundation-sites/dist/js/foundation.min.js',
    'node_modules/chart.js/dist/Chart.min.js',
    'node_modules/chartjs-plugin-annotation/chartjs-plugin-annotation.min.js',
    'node_modules/toastr/build/toastr.min.js',
  ],
  sassPaths: [
    'node_modules/foundation-sites/scss',
    'node_modules/motion-ui/src',
    'node_modules/toastr',
    'node_modules/font-awesome/scss',
  ]
};

function fonts()
{
  return gulp.src(resources.fonts)
      .pipe(gulp.dest('public/fonts'))
      ;
}

function scripts()
{
  return gulp.src(resources.scripts)
      .pipe(concat('vendors.js'))
      .pipe(gulp.dest('public/js'))
  ;
}

function sass()
{
  return gulp.src('scss/app.scss')
    .pipe($.sass({
      includePaths: resources.sassPaths,
      outputStyle: 'compressed'
    })
      .on('error', $.sass.logError))
    .pipe($.postcss([
      autoprefixer({ browsers: ['last 2 versions', 'ie >= 9'] })
    ]))
    .pipe(gulp.dest('public/css'))
  ;
}

gulp.task('sass', sass);
gulp.task('scripts', scripts);
gulp.task('fonts', fonts);
gulp.task('default', gulp.series('sass', 'scripts', 'fonts'));
