(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('searchAdminDisplayFts', {
    bindings: {
      display: '<',
      apiEntity: '<',
      apiParams: '<'
    },
    require: {
      parent: '^crmSearchAdminDisplay'
    },
    templateUrl: '~/crmSearchKitFts/searchAdminDisplayFts.html',
    controller: function($scope, searchMeta) {
      const ts = $scope.ts = CRM.ts('search_kit_fts'),
        ctrl = this;

      this.createSqlName = searchMeta.createSqlName;
      this.permissions = CRM.crmSearchAdmin.permissions;

      this.availableEngines = [
        {id: 'mysql', text: ts('MySQL Fulltext')},
        {id: 'solr', text: ts('Apache Solr')},
        {id: 'typesense', text: ts('TypeSense')},
        {id: 'elastic', text: ts('ElasticSearch')}
      ];

      this.$onInit = function () {
        if (!ctrl.display.settings) {
          ctrl.display.settings = {};
        }
        if (!ctrl.display.settings.preferred_engines) {
          ctrl.display.settings.preferred_engines = ['mysql'];
        }
        ctrl.display.acl_bypass = true;

        this.parent.initColumns({label: true});
        this.display.settings.columns = this.display.settings.columns.filter((col) => this.isColumnAllowed(col.key));
      };

      this.isColumnAllowed = function(key) {
        return key && !CRM.crmSearchAdmin.pseudoFields.find((field) => field.name === key);
      };

      this.onChangeEntityPermission = function() {
        if (ctrl.display.settings.entity_permission.length > 1) {
          ctrl.display.settings.entity_permission_operator = ctrl.display.settings.entity_permission_operator || 'AND';
        } else {
          delete ctrl.display.settings.entity_permission_operator;
        }
      };

      $scope.$watch('$ctrl.display.name', function(newVal, oldVal) {
        if (!newVal) {
          newVal = ctrl.display.label;
        }
        if (newVal !== oldVal) {
          ctrl.display.name = _.capitalize(_.camelCase(newVal));
        }
      });
    }
  });

})(angular, CRM.$, CRM._);
