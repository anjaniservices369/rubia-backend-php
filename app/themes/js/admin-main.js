var app = angular.module("Inkredibletoner", [ 'ngMaterial' ]);

app.service("Setting", [ "$rootScope", function($rootScope) {
	$rootScope.baseUrl = angular.element("#baseUrl").attr("data-url");
	var setting = {
		baseUrl : $rootScope.baseUrl
	};
	return setting;
} ]);

app.controller("ProductViewController", [ '$scope', '$mdDialog', '$http',
		'Setting', '$window',
		function($scope, $mdDialog, $http, Setting, $window) {
			$scope.activeId = '';
			$scope.openModel = function(ev, id) {
				$scope.activeId = id;
				$mdDialog.show({
					locals : {
						parent : $scope
					},
					controller : "MediaEditModelController",
					templateUrl : 'mediaEditTmpl.html',
					targetEvent : ev,
					clickOutsideToClose : true
				});
			};

			$scope.productPrice = {
				deletePrice : function(id, pjaxId) {
					$http({
						method : "GET",
						url : Setting.baseUrl + "product/delete-price?id=" + id
					}).then(function success(response) {
						$window.location.reload();
					}, function error(response) {
						console.log("Error", JSON.stringify(response));
					});
				}
			};

		} ]);

app.controller("MediaController", [ '$scope', '$mdDialog',
		function($scope, $mdDialog) {
			$scope.activeId = '';
			$scope.openModel = function(ev, id) {
				$scope.activeId = id;
				$mdDialog.show({
					locals : {
						parent : $scope
					},
					controller : "MediaEditModelController",
					templateUrl : 'mediaEditTmpl.html',
					targetEvent : ev,
					clickOutsideToClose : true
				});
			}
		} ]);

app
		.controller(
				"MediaEditModelController",
				[
						'$scope',
						'parent',
						'$http',
						'Setting',
						'$mdDialog',
						function($scope, parent, $http, Setting, $mdDialog) {
							$scope.parent = parent;
							$scope.imageUrl = "";
							$scope.model = "";
							$scope.uploadedBy = "";
							$http(
									{
										method : "GET",
										url : Setting.baseUrl
												+ "media/default/detail?id="
												+ $scope.parent.activeId
									}).then(function success(response) {
								$scope.imageUrl = response.data.imageUrl;
								$scope.model = response.data.model;

								$scope.uploadedBy = response.data.uploadedBy;
							}, function error(response) {
								console.log("Error", JSON.stringify(response));
							});

							$scope.saveSettings = function(title, alt) {
								var params = $.param({
									title : title,
									alt : alt,
									_csrf : yii.getCsrfToken()
								});
								$http(
										{
											method : "POST",
											url : Setting.baseUrl
													+ "media/default/save-detail?id="
													+ $scope.parent.activeId,
											data : params,
											headers : {
												'Content-Type' : 'application/x-www-form-urlencoded'
											}
										})
										.then(
												function success(response) {
													$scope.model = response.data.model;
													angular
															.element(
																	".mediaTitle__"
																			+ $scope.parent.activeId)
															.html(
																	$scope.model.title);
													$mdDialog.hide();
												},
												function error(response) {
													console
															.log(
																	"Error",
																	JSON
																			.stringify(response));
												});
							}
						} ]);