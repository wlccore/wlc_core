process.env.TMPDIR = 'cache';

module.exports = function (grunt) {
    var _ = grunt.util._;
    var tmpFilePath = require('tmp').tmpNameSync();
    var localesMap = grunt.file.readJSON('locales.json');
    var locales = _.map(localesMap, function (localeInfo) {
        return localeInfo.locale;
    });
    var localizeTemplateDirs = [
        'root/template'
    ];
    var localizeCodeDirs = [
        'root/classes',
        'root/inc',
        'root/lib'
    ];
    var localeTargetDir = 'root/locale';

    grunt.initConfig({
        dirs: {
            lang: localeTargetDir
        },
        pkg: grunt.file.readJSON('package.json'),
        shell: {
            options: {
                failOnError: true
            },
            extract: {
                command: _.map(locales, function (locale) {
                    var poFilePath = "<%= dirs.lang %>/" + locale + "/LC_MESSAGES/messages.po";
                    return "echo '" + poFilePath + "'\n" +
                        "find . -name '*.php' -print | sort > list.tmp\n" +
                        "xgettext --files-from=list.tmp --language=PHP --no-location --force-po --keyword='pgettext:1c,2' -c -j -o " + poFilePath + "\n" +
                        "rm list.tmp\n";
                }).join("")
            },
            localizeTemplatePOT: {
                command: "echo -n '' > " + tmpFilePath + "\n" + _.map(localizeTemplateDirs, function(templateDir) {
                    return "find " + templateDir + " -name '*.tpl' -print | sort >> " + tmpFilePath + "\n";
                }).join("") + "" +
                "./tpl-gettext-extractor --sort-output --no-location " +
                "--force-po -o <%= dirs.lang %>/messages_tpl.pot --keyword=\"trans\" --keyword=\"transchoice\" --keyword=\"_\" --keyword=\"gettext\"+" +
                " --files `cat " + tmpFilePath + "`\n" +
                "rm " + tmpFilePath + "\n"
            },
            localizeCodePOT: {
                command: "echo -n '' > " + tmpFilePath + "\n" + _.map(localizeCodeDirs, function(codeDir) {
                    return "find " + codeDir + " -name '*.php' -print | sort >> " + tmpFilePath + "\n";
                }).join("\n") + "xgettext -L PHP --no-location --force-po -o " +
                "<%= dirs.lang %>/messages_code.pot -f " + tmpFilePath + "\n" +
                "rm " + tmpFilePath + "\n"
            },
            localizeMergePOT: {
                command: "msgcat -u <%= dirs.lang %>/messages_tpl.pot <%= dirs.lang %>/messages_code.pot | " +
	                "sed 's/^\\\"Project-Id-Version: PACKAGE VERSION\\\\n\\\"/\\\"Project-Id-Version: WLC_CORE <%= pkg.version %>\\\\n\\\"/' " +
	                " | sed 's/^\\\"Content-Type: text\\/plain\; charset=CHARSET\\\\n\\\"/\\\"Content-Type: text\\/plain\; charset=UTF-8\\\\n\\\"/' " +
	                " | sed 's/^\\\"Content-Transfer-Encoding: 8bit\\\\n\\\"//' " +
	                " > <%= dirs.lang %>/messages.pot\n" +
	                "rm <%= dirs.lang %>/messages_tpl.pot <%= dirs.lang %>/messages_code.pot\n"
            },
            swaggerDocs: {
                command: "./vendor/zircote/swagger-php/bin/swagger -b root/version.php -o docs/swagger.json root/classes/RestApi/"
            }
        },
        potomo: {
            dist: {
                files: [{
                    expand: true,
                    cwd: '<%= dirs.lang %>',
                    src: ['**/*.po'],
                    dest: '<%= dirs.lang %>',
                    ext: '.mo',
                    nonull: true
                }]
            }
        },
        copy: {
            swaggerDocs: {
                expand: true,
                src: '**',
                dest: 'docs/',
                cwd: 'vendor/swagger-api/swagger-ui/dist/'
            }
        },
        replace: {
            swaggerDocs: {
                src: ['docs/index.html'],
                overwrite: true,
                replacements: [{
                    from: 'https://petstore.swagger.io/v2/swagger.json',
                    to: 'swagger.json'
                }]
            }
        },
        clean: {
            translations: ['<%= dirs.lang %>/**/*.po~']
        },
        msgInitMerge: {
            phpgettext: {
                src: ['<%= dirs.lang %>/messages.pot'],
                langPath: '<%= dirs.lang %>',
                options: {
                    locales: _.map(locales, function (locale) {
                        var localeArr = locale.split('_');
                        var params = {
                            name: localeArr[0],
                            folder: locale
                        };
                        return params;
                    }),
                    poFilesPath: function() {
                        return localeTargetDir + '/<%= locale%>/LC_MESSAGES/messages.po';
                    }(),
                    msgInit: {
                        cmd: "msginit",
                        opts: {
                            "no-translator": true
                        }
                    }
                }
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-potomo');
    grunt.loadNpmTasks('grunt-shell');
    grunt.loadNpmTasks('grunt-msg-init-merge');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-text-replace');

    grunt.registerTask(
        'default',
        [
            'makemessages'
        ]
    );

    grunt.registerTask(
        'dist',
        [
            'compilemessages'
        ]
    );

    grunt.registerTask(
        'makemessages',
        [
            'shell:localizeTemplatePOT',
            'shell:localizeCodePOT',
            'shell:localizeMergePOT',
            'msgInitMerge',
            'clean:translations'
        ]
    );

    grunt.registerTask(
        'compilemessages',
        [
            'potomo'
        ]
    );

    grunt.registerTask(
        'docs',
        [
            'shell:swaggerDocs',
            'copy:swaggerDocs',
            'replace:swaggerDocs'
        ]
    );

};
