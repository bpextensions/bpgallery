const Encore = require('@symfony/webpack-encore');
const BuildPlugin = require('./.dev/BuildPlugin');

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

// Module build configuration
Encore
    .setOutputPath('media/com_bpgallery')
    .setPublicPath('media/com_bpgallery/')
    .addPlugin(new BuildPlugin)
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .disableSingleRuntimeChunk()
    .enableVersioning(false)
    .enableSassLoader((options) => {
        options.sassOptions = {
            quietDeps: true, // disable warning msg
        }
    })
    .disableSingleRuntimeChunk()
    .enableSourceMaps(!Encore.isProduction())
    .configureBabel((config) => {
        config.plugins.push("@babel/plugin-transform-class-properties")
    }, {
        includeNodeModules: ['swiper', 'dom7', 'ssr-window'],
        useBuiltIns: 'usage',
        corejs: 3,
    })
    .configureTerserPlugin((options) => {
        options.terserOptions = {
            output: {
                comments: false,
            },
            compress: {
                drop_console: true,
            }
        }
    })
    .autoProvidejQuery()
    .enablePostCssLoader()
    .addExternals({
        jquery: 'jQuery',
        joomla: 'Joomla',
    })
    .addStyleEntry('component', [
        './.dev/admin/scss/component.scss',
    ])
    .addEntry('uploader', [
        './.dev/admin/js/uploader.js',
        './.dev/admin/scss/uploader.scss',
    ])
    .addStyleEntry('default', [
        './.dev/site/scss/default.scss',
    ])
    .addStyleEntry('square', [
        './.dev/site/scss/square.scss',
    ])
    .addEntry('masonry', [
        './.dev/site/js/masonry.js',
        './.dev/site/scss/masonry.scss',
    ])
    .addStyleEntry('category-default', [
        './.dev/site/scss/themes/category/default.scss',
    ])
    .addStyleEntry('image-default', [
        './.dev/site/scss/themes/image/default.scss',
    ])
    .addEntry('lightbox', [
        './.dev/site/js/lightbox.js',
    ])
    .copyFiles({
        from: './.dev/admin/images',

        // optional target path, relative to the output dir
        to: 'images/[path][name].[ext]',
    })
    .configureFilenames({
        css: 'css/[name].css',
        js: 'js/[name].js'
    });

const assetsConfig = Encore.getWebpackConfig();

// Export configurations
module.exports = [assetsConfig];