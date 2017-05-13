(function(angular, $, _) {

  // Controller for the "Radio Date" widget
//  // Note: Expects $scope.model to be an object with properties:
//  //   - recipients: array of contacts
  angular.module('crmMailing').controller('CrmMailingRadioCtrl', function($scope) {
    $scope.ts = CRM.ts(null);
    $scope.scheduleMode = 'now';
    $scope.$watch('scheduleMode', function(newValue) {
      if (newValue === 'now') {
        $scope.mailing.scheduled_date = null;
      }
    });
    $scope.$watch('mailing.scheduled_date', function(newValue){
      if (newValue) $scope.scheduleMode = 'at';
    });
  });

})(angular, CRM.$, CRM._);
