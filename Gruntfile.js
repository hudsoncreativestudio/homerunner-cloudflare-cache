module.exports = function (grunt) {
  "use strict";

  let pluginVersion = "";
  const pkgJson = require("./package.json");

  require("matchdep").filterDev("grunt-*").forEach(grunt.loadNpmTasks);

  grunt.getPluginVersion = function () {
    let p = "homerunner-cloudflare-cache.php";
    if (pluginVersion == "" && grunt.file.exists(p)) {
      let source = grunt.file.read(p);
      let found = source.match(/Version:\s(.*)/);
      pluginVersion = found[1];
    }
    return pluginVersion;
  };

  grunt.initConfig({
    pkg: "<json:package.json>",
    compress: {
      main: {
        options: {
          archive:
            "build/homerunner-cloudflare-cache.v" + pkgJson.version + ".zip",
        },
        files: [
          {
            expand: true,
            cwd: ".",
            src: [
              "assets/**",
              "!assets/sass/**", // This line excludes the sass folder
            ],
            dest: "homerunner-cloudflare-cache/",
          },
          { src: "includes/**", dest: "homerunner-cloudflare-cache/" },
          { src: "vendor/**", dest: "homerunner-local/" },
          {
            src: "homerunner-cloudflare-cache.php",
            dest: "homerunner-cloudflare-cache/",
          },
          { src: "index.php", dest: "homerunner-cloudflare-cache/" },
        ],
      },
    },
    "string-replace": {
      inline: {
        files: {
          "./": ["homerunner-cloudflare-cache.php"],
        },
        options: {
          replacements: [
            {
              pattern: "Version: " + grunt.getPluginVersion(),
              replacement: "Version: " + pkgJson.version,
            },
            {
              pattern: "HOMECFCC_VERSION', '" + grunt.getPluginVersion() + "'",
              replacement: "HOMECFCC_VERSION', '" + pkgJson.version + "'",
            },
          ],
        },
      },
    },
  });

  grunt.registerTask("version", ["string-replace"]);
  grunt.registerTask("build", ["version", "compress"]);
};
