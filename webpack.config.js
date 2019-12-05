var Encore = require('@symfony/webpack-encore');

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
        './.dev/js/uploader.js',
        './.dev/scss/component.scss',
    ])
    .copyFiles({
        from: './.dev/images',

        // optional target path, relative to the output dir
        to: 'images/[path][name].[ext]',
    });

// Export configurations
module.exports = Encore.getWebpackConfig();