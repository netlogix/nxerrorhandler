/*global angular:false */
(function(window, angular, undefined) {
	'use strict';

	var app = angular.module('netlogix.projectName', [
		'ngRaven',
		'netlogix.variables' // must be the last module!
	]);

	app.config(['$ravenProvider', 'netlogix.projectName.development', function($ravenProvider, development) {
		development = development || true;
		$ravenProvider.development(development);
	}]);

}(window, angular));