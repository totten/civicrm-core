const $ = window.CRM.$;

// Simple wrapper around $.crmDatepicker.
// example with no time input: <input crm-ui-datepicker="{time: false}" ng-model="myobj.datefield"/>
// example with custom date format: <input crm-ui-datepicker="{date: 'm/d/y'}" ng-model="myobj.datefield"/>

function crmUiDatepicker($timeout) {
  return {
    restrict: 'AE',
    require: 'ngModel',
    scope: {
      crmUiDatepicker: '='
    },
    link: function (scope, element, attrs, ngModel) {
      ngModel.$render = function () {
        const viewVal = ngModel.$viewValue || '';
        // Prevent unnecessarily triggering ngChagne
        if (element.val() != viewVal) {
          element.val(viewVal).change();
        }
      };
      let settings = angular.copy(scope.crmUiDatepicker || {});
      // Set defaults to be non-restrictive
      settings.start_date_years = settings.start_date_years || 100;
      settings.end_date_years = settings.end_date_years || 100;

      // Wait for interpolated elements like {{placeholder}} to render
      $timeout(function() {
        element
          .crmDatepicker(settings)
          .on('change', function () {
            // Because change gets triggered from the $render function we could be either inside or outside the $digest cycle
            $timeout(function() {
              let requiredLength = 19;
              if (settings.time === false) {
                requiredLength = 10;
              }
              if (settings.date === false) {
                requiredLength = 8;
              }
              else if (typeof settings.date === 'string') {
                const lowerFormat = settings.date.toLowerCase();
                // FIXME: parseDate doesn't work with incomplete date formats; skip validation if no month, day or year in format
                if (lowerFormat.indexOf('y') < 0 || lowerFormat.indexOf('m') < 0 || lowerFormat.indexOf('d') < 0) {
                  // skipping the validation by setting the actual length of datepicker value
                  requiredLength = element.val().length;
                }
              }
              ngModel.$setValidity('incompleteDateTime', !(element.val().length && element.val().length !== requiredLength));
            });
          });
      });
    }
  };

}

export default crmUiDatepicker;
