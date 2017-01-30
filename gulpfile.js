var gulp = require('gulp');
var postcss = require('gulp-postcss');
var sourcemaps = require('gulp-sourcemaps');
var uglify = require('gulp-uglify');
var imageMin = require('gulp-imagemin');
var rename = require('gulp-rename');
var concat = require('gulp-concat');
var clean = require('rimraf');
var removeCode = require('gulp-remove-code');
var babel = require('gulp-babel');
var rsync = require('gulp-rsync');
var runSequence = require('run-sequence');
var template = require('gulp-template');
var fs = require('fs');
var newer = require('gulp-newer');
var cleanCss = require('gulp-clean-css');
var sorting = require('postcss-sorting');
var pug = require('gulp-pug');

/**

 * pluginName/js contains a src and dist directory.  dist will contain the optimized and minimized code
 *
 * pluginName/*style.scss contains the styles and the comments to let WordPress know the Theme Name.  The theme name 
 * is grabbed from package.json. It is converted to css with the name style.css. 
 *
 * All custom js code can be ES6.

 *
 * Any js code you don't want in the dist production code, you can wrap it like the following example.  Change the 
 * variable 'production' below.
 *     //removeIf(production)
 *      console.log(dVar);
 *     //endRemoveIf(production)
 *
 */

    //variables
var pkg = require('./package.json');
var pluginName = pkg.name;

var production = false;
var jsConcatenated = 'scripts.js';
// All scripts listed in this array will be concatenated into a single js file with the name from jsConcatenated.
// Be sure to enqueue the jsConcatenated file in the functions.php file.  Should be relative to the /js/src directory OR 
// if using npm as package manager, include full path starting with 'node_modules/'
var jsScripts = ['my_scripts.js'];

//Create a variable containing all scripts with path 
var jsScriptsWithPath = jsScripts.map( function (s) {
     return pluginName + '/js/src/' + s;
});

console.log(jsScriptsWithPath);
//Create a variable containing all scripts with path with a negated ! in front for tasks we want to have these scripts excluded
var negatedJsScriptsWithPath = jsScripts.map( function (s) {
    return '!' + pluginName + '/js/src/' + s;
});


/**
 * Cleaning tasks
 */

gulp.task('cleanImages', function(cb) {
    clean('images/dist', cb);
});

gulp.task('cleanScripts', function(cb){
    clean(pluginName + '/js/dist', cb);
});

gulp.task('clean', ['cleanImages', 'cleanScripts']);

/**
 * Copying vendor or separate js files - js files you don't want concatenated with others
 *   will need to enqueue them separately in php.  If you want a vendor or other js file concatenated, place 
 *   the js file name in the jsScripts array above in the order it should be concatenated.
 */
gulp.task('vendorjs', function() {
    var scripts = negatedJsScriptsWithPath.concat([pluginName + '/js/src/**/*.js', '!'+pluginName+'/js/src/**/*.min.js']);
     return gulp.src(scripts)  //include all js under src except for js to be concatenated
       .pipe(gulp.dest(pluginName + '/js/dist'))
       .pipe(rename({extname: '.min.js'}))
       .pipe(sourcemaps.init())
       .pipe(uglify())
       .pipe(sourcemaps.write('.'))
       .pipe (gulp.dest(pluginName + '/js/dist/'));
});


/**
 * Concatenate and minify all JavaScript scripts in the jsScripts array
 * Any non module based javascript (no requires) so no browserify needed.
 * */
gulp.task('other-scripts', function() {
     return gulp.src(jsScriptsWithPath)
         .pipe(concat(jsConcatenated))
        .pipe(babel())
        .pipe(removeCode({ production: production}))
        .pipe(gulp.dest(pluginName + '/js/dist'))
        .pipe(rename({extname: '.min.js'}))
        .pipe(sourcemaps.init())
        .pipe(uglify())
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest(pluginName + '/js/dist'));
 //       .pipe(browserSync.stream());
});

/**
 *  This task copies all other files like css or images needed by js scripts, usually vendor js
 */
gulp.task('script-assets', function(){
    return gulp.src([pluginName + '/js/src/**/*', '!' + pluginName + '/js/src/**/*.js'])
        .pipe(gulp.dest( pluginName + '/js/dist/'));
});
gulp.task('script-vendor-minified', function(){
    return gulp.src([pluginName + '/js/src/**/*.min.js'])
        .pipe(gulp.dest( pluginName + '/js/dist/'));
});

gulp.task('copy:vue', function () {
    let version = pkg.dependencies.vue.match(/\d+\.\d+\.\d+/)[0];
    return gulp.src(['node_modules/vue/dist/vue.min.js'])
        .pipe(rename('vue-@' + version + '.min.js'))
        .pipe(gulp.dest(pluginName + '/js/dist/vendor'));
});
gulp.task('copy:leaflet', function () {
    let version = pkg.dependencies.leaflet.match(/\d+\.\d+\.\d+/)[0];
    return gulp.src(['node_modules/leaflet/dist/**/*']) //already minimized
        .pipe(gulp.dest(pluginName + '/js/dist/vendor/leaflet@'+version+'/'));
});

/*gulp.task('copy:leaflet', function () {
    let version = pkg.dependencies.leaflet.match(/\d+\.\d+\.\d+/)[0];
    return gulp.src(['node_modules/leaflet/dist/leaflet.js','node_modules/leaflet/dist/leaflet.css']) //already minimized
        .pipe(rename(function(path) {
            path.basename = 'leaflet-@' + version;
        } ))
        .pipe(gulp.dest(pluginName + '/js/dist/vendor'));
});*/
gulp.task('copy', function(done){
    runSequence(
        'copy:vue',
        'copy:leaflet',
        function(){done()});
});
/* 
'images' looks in the images/src directory which is not in the same tree as the themename.  It creates optimized images
in the /images/dist directory.  These can be manually moved to the themename/images folder or uploaded to the wordpress
site if the image is not theme specific.
 */

gulp.task('images', function() {
    gulp.src(['images/src/**/*']).
        pipe(newer('images/dist'))
        .pipe(imageMin({
            progressive: true

        }))
        .pipe(gulp.dest('images/dist'));
//        .pipe(browserSync.stream())
});

/*  Styles
--------------------------------------------------------------------------------
 */
gulp.task('sortScss', function() {
    var scssSortingConfig = JSON.parse(fs.readFileSync('./.scssSorting'));
   return gulp.src([ pluginName + '/**/*.scss'])
       .pipe(postcss([sorting(scssSortingConfig)]))
       .pipe(gulp.dest(pluginName));
});

gulp.task('styles', function() {
    return gulp.src([ pluginName + '/**/*.scss'])
        .pipe(sourcemaps.init())
        .pipe(template({pkg: pkg, environment: production}))
        .pipe(postcss([require('precss'), require('postcss-calc')({warnWhenCannotResolve: true}), require('autoprefixer')({ browsers: ['last 2 versions'] })]))
        .pipe(cleanCss())
        .pipe(rename(function(path){
            path.extname = '.css'
        }))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest( pluginName ));
     //   .pipe(browserSync.stream());
});

gulp.task('views', function() {
    return gulp.src(pluginName + '/views/*.pug')
        .pipe(pug({pretty: true}))
        .pipe(rename(function(path){
            path.extname = '.html'
        }))
        .pipe(gulp.dest(pluginName + '/views/'))
});

/* setup ssh keys so no login or password is required when run */
/* BE CAREFUL! This config will erase anything on the remote side that is not on the local side.  Make sure you have the right directory! */
var remote = require('./rsync-jhtech.json');
gulp.task('deploy', function() {
    return gulp.src(pluginName + '/**')
        .pipe(rsync({
            hostname: remote.hostname,
  //          destination: '~/public_html/wp-content/themes/' + pluginName,
            // ~/staging/3/wp-content/themes/
            destination: remote.destination + pluginName,
            root: pluginName,
            username: remote.username,
            port: remote.port,
            incremental: true,
            progress: true,
            recursive: true,
            clean: true

        }))

});


/* when certain files change - these tasks make sure they are run in sequence */


gulp.task('deploy-other-scripts', function(done) {
    runSequence('other-scripts', 'script-assets', 'vendorjs', 'script-vendor-minified','views','copy','deploy', function() { done(); });
});

gulp.task('deploy-styles', function(done) {
    runSequence('sortScss','styles', 'deploy', function() { done(); });
});

gulp.task('clean-build', function(done) {
    runSequence('other-scripts', 'script-assets', 'vendorjs', 'sortScss','styles', 'deploy', function() { done(); });
});

gulp.task('default', ['deploy-styles',  'images', 'views', 'deploy-other-scripts'], function() {
    gulp.watch(pluginName + '/**/*.scss', ['deploy-styles']);
    gulp.watch(pluginName +'/js/src/**/*.*', ['deploy-other-scripts']);
    gulp.watch(pluginName + '/views/**/*', ['deploy-other-scripts']);
    gulp.watch(pluginName + '/**/*.php', ['deploy']);
    gulp.watch('src/images/**.*', ['images']);
});



/*
var now = new Date(),
    year = now.getUTCFullYear(),
    month = now.getMonth() + 1,
    day = now.getDate(),
    hour = now.getHours(),
    minutes = now.getMinutes();
month = month < 10 ? '0' + month : month;
day = day < 10 ? '0' + day : day;
hour = hour < 10 ? '0' + hour : hour;
minutes = minutes < 10 ? '0' + minutes : minutes;
nowString = year + '-' + month + '-' + day + ' at ' + hour + ':' + minutes; 
*/