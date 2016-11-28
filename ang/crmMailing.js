(function (angular, $, _) {

  angular.module('crmMailing', [
    'crmUtil', 'crmAttachment', 'crmAutosave', 'ngRoute', 'ui.utils', 'crmUi', 'dialogService'
  ]);

  angular.module('crmMailing').config([
    '$routeProvider',
    function ($routeProvider) {
      $routeProvider.when('/mailing', {
        template: '<div></div>',
        controller: 'ListMailingsCtrl'
      });

      if (!CRM || !CRM.crmMailing) {
        return;
      }

      angular.forEach(CRM.crmMailing.layouts, function(editTemplate, pathSuffix) {
        $routeProvider.when('/mailing/new' + pathSuffix, {
          template: '<p>' + ts('Initializing...') + '</p>',
          controller: 'CreateMailingCtrl',
          resolve: {
            selectedMail: function(crmMailingMgr) {
              var m = crmMailingMgr.create();
              return crmMailingMgr.save(m);
            }
          }
        });
        $routeProvider.when('/mailing/:id' + pathSuffix, {
          templateUrl: editTemplate,
          controller: 'EditMailingCtrl',
          resolve: {
            selectedMail: function($route, crmMailingMgr) {
              return crmMailingMgr.get($route.current.params.id);
            },
            attachments: function($route, CrmAttachments) {
              var attachments = new CrmAttachments(function () {
                return {entity_table: 'civicrm_mailing', entity_id: $route.current.params.id};
              });
              return attachments.load();
            }
          }
        });
      });
    }
  ]);

})(angular, CRM.$, CRM._);
