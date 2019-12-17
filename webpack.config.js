var Encore = require('@symfony/webpack-encore');

// ADMIN CONFIGURATION ---------------------------------------

// Module build configuration
Encore
    .setOutputPath('administrator/components/com_bpgallery/assets')
    .setPublicPath('/administrator/components/com_bpgallery/assets')
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
    .addEntry('component', [
        './.dev/site/js/component.js',
        './.dev/site/scss/component.scss',
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

// Export configurations
module.exports = [adminConfig, siteConfig];