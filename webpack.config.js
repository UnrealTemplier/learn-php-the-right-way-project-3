const Encore = require("@symfony/webpack-encore")

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || "dev")
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath("public/build/")

    // public path used by the web server to access the output path
    .setPublicPath("/build")

    /*
     * ENTRY CONFIG
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    .addEntry("app", "./resources/js/app.js")
    .addEntry("dashboard", "./resources/js/dashboard.js")
    .addEntry("categories", "./resources/js/categories.js")
    .addEntry("transactions", "./resources/js/transactions.js")
    .addEntry("auth", "./resources/js/auth.js")
    .addEntry("verify", "./resources/js/verify.js")
    .addEntry("profile", "./resources/js/profile.js")
    .addEntry("forgot_password", "./resources/js/forgot_password.js")

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())

    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning()

    .configureBabel((config) => {
        config.plugins.push("@babel/plugin-proposal-class-properties")
    })

    // enables @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = "usage"
        config.corejs      = 3
    })

    .configureWatchOptions(function (watchOptions) {
        // enable polling and check for changes every 250ms
        // polling is useful when running Encore inside a Virtual Machine
        watchOptions.poll = 250;
    })

    .copyFiles({
                   from   : "./resources/images",
                   to     : "images/[path][name].[hash:8].[ext]",
                   pattern: /\.(png|jpg|jpeg|gif)$/
               })

    // enables Sass/SCSS support
    .enableSassLoader()

module.exports = Encore.getWebpackConfig()
