/* jshint node:true */
module.exports = function( grunt ){
	'use strict';

	grunt.initConfig({
		// setting folder templates
		dirs: {
			build: 'tmp/build',
			svn: 'tmp/release-svn'
		},

		copy: {
			main: {
				src: [
					'**',
					'!*.log', // Log Files
					'!node_modules/**', '!Gruntfile.js', '!package.json','!package-lock.json', // NPM/Grunt
					'!.git/**', '!.github/**', // Git / Github
					'!tests/**', '!bin/**', '!phpunit.xml', '!phpunit.xml.dist', // Unit Tests
					'!vendor/**', '!composer.lock', '!composer.phar', '!composer.json', // Composer
					'!.*', '!**/*~', '!tmp/**', //hidden/tmp files
					'!CONTRIBUTING.md',
					'!readme.md',
					'!phpcs.ruleset.xml',
					'!tools/**'
				],
				dest: '<%= dirs.build %>/'
			}
		},

		// Generate POT files.
		makepot: {
			options: {
				type: 'wp-plugin',
				domainPath: '/languages',
				potHeaders: {
					'report-msgid-bugs-to': 'https://github.com/wpdrift/WPdrift-IO/issues',
					'language-team': 'LANGUAGE <EMAIL@ADDRESS>'
				}
			},
			dist: {
				options: {
					potFilename: 'wpdrift-worker.pot',
					exclude: [
						'apigen/.*',
						'tests/.*',
						'tmp/.*',
						'vendor/.*',
						'node_modules/.*'
					]
				}
			}
		},

		// Check textdomain errors.
		checktextdomain: {
			options:{
				text_domain: 'wpdrift-worker',
				keywords: [
					'__:1,2d',
					'_e:1,2d',
					'_x:1,2c,3d',
					'esc_html__:1,2d',
					'esc_html_e:1,2d',
					'esc_html_x:1,2c,3d',
					'esc_attr__:1,2d',
					'esc_attr_e:1,2d',
					'esc_attr_x:1,2c,3d',
					'_ex:1,2c,3d',
					'_n:1,2,4d',
					'_nx:1,2,4c,5d',
					'_n_noop:1,2,3d',
					'_nx_noop:1,2,3c,4d'
				]
			},
			files: {
				src:  [
					'**/*.php',         // Include all files
					'!apigen/**',       // Exclude apigen/
					'!node_modules/**', // Exclude node_modules/
					'!tests/**',        // Exclude tests/
					'!vendor/**',       // Exclude vendor/
					'!tmp/**'           // Exclude tmp/
				],
				expand: true
			}
		},

		addtextdomain: {
			wpdriftworker: {
				options: {
					textdomain: 'wpdrift-worker'
				},
				files: {
					src: [
						'*.php',
						'**/*.php',
						'!node_modules/**'
					]
				}
			}
		},

		wp_deploy: {
			deploy: {
				options: {
					plugin_slug: 'wpdrift-worker',
					build_dir: '<%= dirs.build %>',
					tmp_dir: '<%= dirs.svn %>/',
					max_buffer: 1024 * 1024
				}
			}
		},

		zip: {
			'main': {
				cwd: '<%= dirs.build %>/',
				src: [ '<%= dirs.build %>/**' ],
				dest: 'tmp/wpdrift-worker.zip'
			}
		},

		clean: {
			main: [ 'tmp/' ], //Clean up build folder
		},

		checkrepo: {
			deploy: {
				tagged: true,
				clean: true
			}
		},

		wp_readme_to_markdown: {
			readme: {
				files: {
					'readme.md': 'readme.txt'
				}
			}
		}
	});

	// Load NPM tasks to be used here
	grunt.loadNpmTasks( 'grunt-checktextdomain' );
	grunt.loadNpmTasks( 'grunt-contrib-copy' );
	grunt.loadNpmTasks( 'grunt-contrib-clean' );
	grunt.loadNpmTasks( 'grunt-gitinfo' );
	grunt.loadNpmTasks( 'grunt-checkbranch' );
	grunt.loadNpmTasks( 'grunt-wp-deploy' );
	grunt.loadNpmTasks( 'grunt-checkrepo' );
	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown');
	grunt.loadNpmTasks( 'grunt-zip' );

	grunt.registerTask( 'build', [ 'gitinfo', 'test', 'clean', 'copy' ] );

	grunt.registerTask( 'deploy', [ 'checkbranch:master', 'checkrepo', 'build', 'wp_deploy' ] );
	grunt.registerTask( 'deploy-unsafe', [ 'build', 'wp_deploy' ] );

	grunt.registerTask( 'package', [ 'build', 'zip' ] );

	// Register tasks
	grunt.registerTask( 'default', [
		'wp_readme_to_markdown'
	] );

	// Just an alias for pot file generation
	grunt.registerTask( 'pot', [
		'makepot'
	] );

	grunt.registerTask( 'test', [
		'phpunit'
	] );

	grunt.registerTask( 'dev', [
		'test',
		'default'
	] );
};
