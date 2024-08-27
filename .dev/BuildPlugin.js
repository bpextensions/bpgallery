const fs = require('fs');
const path = require('path');
const crc32 = require('crc/crc32');
const {Exception} = require("sass");

/**
 * Plugin that fixes assets naming to match Joomla! 5 naming convention.
 */
class BuildPlugin {

    /**
     * @param {Compiler} compiler the compiler instance
     */
    apply(compiler) {

        const media_path = path.dirname(__dirname) + '/media/com_bpgallery';

        // Clear old resources
        compiler.hooks.compile.tap('BuildPlugin', (compilation) => {

            const path_css = media_path + '/css';
            const path_js = media_path + '/js';
            const path_images = media_path + '/images';
            const path_assets = media_path + '/joomla.asset.json';
            const path_manifest = media_path + '/manifest.json';
            const path_entrypoints = media_path + '/entrypoints.json';

            if (fs.existsSync(path_css)) {
                fs.rmSync(path_css, {recursive: true, force: true});
            }

            if (fs.existsSync(path_js)) {
                fs.rmSync(path_js, {recursive: true, force: true});
            }

            if (fs.existsSync(path_images)) {
                fs.rmSync(path_images, {recursive: true, force: true});
            }

            if (fs.existsSync(path_assets)) {
                fs.rmSync(path_assets, {force: true});
            }

            if (fs.existsSync(path_manifest)) {
                fs.rmSync(path_manifest, {force: true});
            }

            if (fs.existsSync(path_entrypoints)) {
                fs.rmSync(path_entrypoints, {force: true});
            }

            return false;
        });

        let styles = {};
        let scripts = {};
        let assets = {};

        function detailsFromPath(assetPath, versionedPath) {

            const ext = path.extname(assetPath).substring(1);
            const parts = assetPath.split('/').splice(1);
            const filename = path.basename(versionedPath);
            let name = parts.join('.');
            name = name.substring(0, name.length - ext.length - 1);

            if (ext === 'css') {
                styles[versionedPath] = name;

                return {
                    'name': name,
                    'type': 'style',
                    'uri': parts[0] + '/' + filename,
                }
            } else if (ext === 'js') {
                scripts[versionedPath] = name;

                return {
                    'name': name,
                    'type': 'script',
                    'uri': parts[0] + '/' + filename,
                }
            }

            return false;
        }

        function presetsDetailsFromEntrypoint(entrypoint, details) {

            let dependencies = [];

            if (details.css) {
                for (let assetPath of details.css) {
                    if (assetPath in styles) {
                        dependencies.push(styles[assetPath] + "#style");
                    }
                }
            }
            if (details.js) {
                for (let assetPath of details.js) {
                    if (assetPath in scripts) {
                        dependencies.push(scripts[assetPath] + "#script");
                    }
                }
            }

            return {
                "name": entrypoint,
                "type": "preset",
                "dependencies": dependencies
            }
        }

        // Rewrite assets definition
        compiler.hooks.done.tap('BuildPlugin', (compilation) => {

            // Create assets file
            let joomla_assets_buffer = fs.readFileSync(__dirname + '/joomla.assets.json');
            let joomla_assets = JSON.parse(joomla_assets_buffer.toString());
            let manifest_buffer = fs.readFileSync(media_path + '/manifest.json');
            let manifest = JSON.parse(manifest_buffer.toString());
            let entrypoints_buffer = fs.readFileSync(media_path + '/entrypoints.json');
            let entrypoints = JSON.parse(entrypoints_buffer.toString()).entrypoints;
            let version = crc32(process.hrtime()).toString(16);

            // Write details about each asset
            for (const assetPath in manifest) {
                const details = detailsFromPath(assetPath, manifest[assetPath]);

                if (details !== false) {
                    details.version = version;
                    joomla_assets.assets.push(details);
                }
            }

            // Build presets
            for (const entrypoint in entrypoints) {
                const details = presetsDetailsFromEntrypoint(entrypoint, entrypoints[entrypoint]);

                if (details !== false) {
                    joomla_assets.assets.push(details);
                }
            }

            try {
                fs.writeFileSync(media_path + '/joomla.assets.json', JSON.stringify(joomla_assets));
            } catch (e) {
                return false;
            }

            return true;
        });

    }

}

module.exports = BuildPlugin