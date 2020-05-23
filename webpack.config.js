var Encore = require('@symfony/webpack-encore');

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}
// ADMIN CONFIGURATION ---------------------------------------

// Module build configuration
Encore
    .setOutputPath('administrator/components/com_bpgallery/assets')
    .setPublicPath('administrator/components/com_bpgallery/assets/')
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSassLoader()
    .disableSingleRuntimeChunk()
    .enableSourceMaps(!Encore.isProduction())
    .configureBabel(() => {
    }, {
        useBuiltIns: 'usage',
        corejs: 3
    })
    .addExternals({
        jquery: 'jQuery',
        joomla: 'Joomla',
    })
    .addEntry('component', [
        './.dev/admin/js/uploader.js',
        './.dev/admin/scss/component.scss',
    ])
    .copyFiles({
        from: './.dev/admin/images',

        // optional target path, relative to the output dir
        to: 'images/[path][name].[ext]',
    });

const adminConfig = Encore.getWebpackConfig();

// FRONT-END CONFIGURATION ---------------------------------------

// Module build configuration
Encore.reset();
Encore
    .setOutputPath('components/com_bpgallery/assets')
    .setPublicPath('/components/com_bpgallery/assets')
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSassLoader()
    .disableSingleRuntimeChunk()
    .enableSourceMaps(!Encore.isProduction())
    .configureBabel(() => {
    }, {
        useBuiltIns: 'usage',
        corejs: 3
    })
    .addExternals({
        jquery: 'jQuery',
        joomla: 'Joomla',
    })
    .addEntry('default', [
        './.dev/site/scss/default.scss',
    ])
    .addEntry('square', [
        './.dev/site/scss/square.scss',
    ])
    .addEntry('masonry', [
        './.dev/site/js/masonry.js',
        './.dev/site/scss/masonry.scss',
    ])
    .addStyleEntry('category-default', [
        './.dev/site/scss/themes/category/default.scss',
    ])
    .addEntry('lightbox', [
        './.dev/site/js/lightbox.js',
    ])
    .copyFiles({
        from: './.dev/site/images',

        // optional target path, relative to the output dir
        to: 'images/[path][name].[ext]',
    });

const siteConfig = Encore.getWebpackConfig();

// MEDIA CONFIGURATION ---------------------------------------

// Module build configuration
Encore.reset();
Encore
    .setOutputPath('media/com_bpgallery')
    .setPublicPath('/media/com_bpgallery/')
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSassLoader()
    .disableSingleRuntimeChunk()
    .enableVersioning(false)
    .enableSourceMaps(!Encore.isProduction())
    .configureBabel(function (babelConfig) {
    }, {})
    .addExternals({
        jquery: 'jQuery',
        joomla: 'Joomla',
    })
    .addEntry('modal_image', './.dev/media/js/modal_image.js')
    .configureFilenames({
        css: 'css/[name].css',
        js: 'js/[name].js'
    });

const mediaConfig = Encore.getWebpackConfig();

// Export configurations
module.exports = [adminConfig, siteConfig, mediaConfig];