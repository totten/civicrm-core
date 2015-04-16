(function(angular, $, _) {

  // Round a number (n) while preserving the first few significant digits.
  var roundSigfig = window.roundSigfig = function(n, sigfig) {
    var len = ("" + n).length;
    for (var i = sigfig; i < len; i++) {
      n /= 10.0;
    }
    n = Math.round(n);
    for (i = sigfig; i < len; i++) {
      n *= 10.0;
    }
    return n;
  };

  // Controller for the edit-recipients fields (
  // WISHLIST: Move most of this to a (cache-enabled) service
  // Scope members:
  //  - [input] mailing: object
  //  - [output] recipients: array of recipient records
  angular.module('crmMailing').controller('EditRecipCtrl', function EditRecipCtrl($scope, dialogService, crmApi, crmMailingMgr, $q, crmMetadata, crmStatus, crmUiAlert) {
    // Time to wait before triggering AJAX update to recipients list
    var RECIPIENTS_DEBOUNCE_MS = 100;
    var RECIPIENTS_PREVIEW_LIMIT = 10000;

    var ts = $scope.ts = CRM.ts(null);

    $scope.isMailingList = function isMailingList(group) {
      var GROUP_TYPE_MAILING_LIST = '2';
      return _.contains(group.group_type, GROUP_TYPE_MAILING_LIST);
    };

    var counts = $scope.counts = {incMin: 0, incMax: 0, excMin: 0, excMax: 0, totMin: 0, totMax: 0, totMean: 0, totRange: 0};
    $scope.getRecipientsEstimate = function() {
      if (counts.incMax === 0) {
        return ts('No contacts');
      }
      //return (counts.totMin == counts.totMax) ? ts('~%1 recipients', {1: counts.totMin}) : ts('%1 to %2 recipients', {1: counts.totMin, 2: counts.totMax});
      return ts('~%1 contacts', {1: roundSigfig(counts.totMean, counts.totMean < 100 ? 1 : 2)});
    };

    $scope.createRecipAlert = function createRecipAlert() {
      return crmUiAlert({
        type: 'info',
        title: ts('Quick Estimate'),
        templateUrl: '~/crmMailing/EditRecipCtrl/CountAlert.html',
        options: {expires: 0},
        scope: angular.extend($scope.$new(), {
          counts: counts,
          previewRecipients: $scope.previewRecipients,
          ts: ts
        })
      });
    };

    // We monitor four fields -- use debounce so that changes across the
    // four fields can settle-down before AJAX.
    var refreshRecipients = _.debounce(function() {
      $scope.$apply(function() {
        _.extend(counts, {incMin: 0, incMax: 0, excMin: 0, excMax: 0, totMin: 0, totMax: 0, totMean: 0, totRange: 0});
        if (!$scope.mailing) {
          return;
        }

        _.each($scope.mailing.recipients.groups.include, function(includeId) {
          var c = parseInt(CRM.crmMailing.groupCounts[includeId].count);
          counts.incMin = Math.max(counts.incMin, c);
          counts.incMax += c;
        });
        _.each($scope.mailing.recipients.groups.exclude, function(includeId) {
          counts.excMax += parseInt(CRM.crmMailing.groupCounts[includeId].count);
        });
        counts.totMin = Math.max(0, counts.incMin - counts.excMax);
        counts.totMax = counts.incMax;
        counts.totMean = Math.round((counts.totMin + counts.totMax) / 2);
        counts.totRange = Math.round((counts.totMax - counts.totMin) / 2);
      });
    }, RECIPIENTS_DEBOUNCE_MS);
    $scope.$watchCollection("mailing.recipients.groups.include", refreshRecipients);
    $scope.$watchCollection("mailing.recipients.groups.exclude", refreshRecipients);
    $scope.$watchCollection("mailing.recipients.mailings.include", refreshRecipients);
    $scope.$watchCollection("mailing.recipients.mailings.exclude", refreshRecipients);

    $scope.previewRecipients = function previewRecipients() {
      return crmStatus({start: ts('Previewing')}, crmMailingMgr.previewRecipients($scope.mailing, RECIPIENTS_PREVIEW_LIMIT)).then(function(recipients) {
        var model = {
          recipients: recipients
        };
        var options = CRM.utils.adjustDialogDefaults({
          width: '40%',
          autoOpen: false,
          title: ts('Preview (%1)', {
            1: $scope.getRecipientsEstimate()
          })
        });
        dialogService.open('recipDialog', '~/crmMailing/PreviewRecipCtrl.html', model, options);
      });
    };

    // Open a dialog for editing the advanced recipient options.
    $scope.editOptions = function editOptions(mailing) {
      var options = CRM.utils.adjustDialogDefaults({
        autoOpen: false,
        width: '40%',
        height: 'auto',
        title: ts('Edit Options')
      });
      $q.when(crmMetadata.getFields('Mailing')).then(function(fields) {
        var model = {
          fields: fields,
          mailing: mailing
        };
        dialogService.open('previewComponentDialog', '~/crmMailing/EditRecipOptionsDialogCtrl.html', model, options);
      });
    };
  });

})(angular, CRM.$, CRM._);
