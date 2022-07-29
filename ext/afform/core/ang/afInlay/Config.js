(function(angular, $, _) {

  angular.module('afInlay').config(function($routeProvider) {
      $routeProvider.when('/inlays/afform/:id', {
        controller: 'afInlayConfigCtrl',
        templateUrl: '~/afInlay/Config.html',
        resolve: {
          prefetch: function($route, crmApi4) {
            var prefetch = {};
            prefetch.inlayTypes = ['InlayType', 'get', {}, 'class'];
            prefetch.afforms = ['Afform', 'get', {select: ['id', 'name', 'title']}, 'name'];
            if ($route.current.params.id > 0) {
              prefetch.inlay = ['Inlay', 'get', {where: [["id", "=", $route.current.params.id]]}, 0];
            }
            return crmApi4(prefetch);
          }
        }
      });
    }
  );

  // FIXME: Use a component or afform
  angular.module('afInlay').controller('afInlayConfigCtrl', function($scope, crmApi4, crmNavigator, crmStatus, crmUiHelp, prefetch) {
    var ts = $scope.ts = CRM.ts('afInlay');
    var hs = $scope.hs = crmUiHelp({file: 'CRM/afInlay/Config'}); // See: templates/CRM/afInlay/Config.hlp

    $scope.inlayType = prefetch.inlayTypes['Civi\\Afform\\AfformInlay'];
    $scope.inlay = prefetch.inlay || {
      'class' : $scope.inlayType.class,
      name: 'New ' + $scope.inlayType.name,
      public_id: 'new',
      id: 0,
      config: JSON.parse(JSON.stringify($scope.inlayType.defaultConfig)),
    };
    $scope.afforms = prefetch.afforms;

    $scope.afformTitle = function afformTitle(afform) {
      return afform ? afform.title + ' <' + afform.name + '>' : ts('Unknown title');
    };

    $scope.save = function save() {
      return crmStatus(
        {start: ts('Saving...'), success: ts('Saved')},
        crmApi4('Inlay', 'save', { records: [$scope.inlay] })
      ).then(function(r) {
        crmNavigator.redirect(CRM.url('civicrm/a/#/inlays'));
      });

    };
  });

})(angular, CRM.$, CRM._);
