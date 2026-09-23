(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('searchAdminDisplayEntity', {
    bindings: {
      display: '<',
      apiEntity: '<',
      apiParams: '<'
    },
    require: {
      parent: '^crmSearchAdminDisplay'
    },
    templateUrl: '~/crmSearchAdmin/displays/searchAdminDisplayEntity.html',
    controller: function($scope, crmApi4, crmUiHelp, searchMeta) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;
      $scope.hs = crmUiHelp({file: 'CRM/Search/Help/DisplayTypeEntity'});
      this.createSqlName = searchMeta.createSqlName;

      this.permissions = CRM.crmSearchAdmin.permissions;
      this.dataModes = [];
      angular.forEach(CRM.crmSearchAdmin.skDataModes, function(backend, id) {
        ctrl.dataModes.push({id: id, text: backend.label});
      });
      ctrl.isDataMode = (m) => (m == (ctrl.display.settings.data_mode || 'table'));

      this.getFieldFlags = function() {
        const mode = ctrl.display.settings.data_mode || 'table';
        const backend = CRM.crmSearchAdmin.skDataModes && CRM.crmSearchAdmin.skDataModes[mode];
        if (!backend || !backend.fieldFlags) {
          return [];
        }
        return backend.fieldFlags.map(function(flagStr) {
          const parts = flagStr.split(':');
          return {
            name: parts[0],
            default: parts[1] || 'off',
            label: _.startCase(parts[0])
          };
        });
      };

      this.initColFlags = function(col) {
        if (!col.flags) {
          col.flags = {};
        }
        angular.forEach(ctrl.getFieldFlags(), function(flag) {
          if (col.flags[flag.name] === undefined) {
            col.flags[flag.name] = flag.default;
          }
        });
      };

      $scope.$watch('$ctrl.display.settings.data_mode', function(newMode, oldMode) {
        if (newMode !== oldMode && ctrl.display.settings && ctrl.display.settings.columns) {
          angular.forEach(ctrl.display.settings.columns, function(col) {
            ctrl.initColFlags(col);
          });
        }
      });

      this.$onInit = function () {
        ctrl.jobFrequency = CRM.crmSearchAdmin.jobFrequency;
        if (!ctrl.display.settings) {
          ctrl.display.settings = {
            sort: ctrl.parent.getDefaultSort()
          };
        }
        // Entity displays always bypass ACLs
        ctrl.display.acl_bypass = true;
        if (ctrl.display.id && !ctrl.display._job) {
          crmApi4({
            ref: ['SK_' + ctrl.display.name, 'getRefreshDate', {}, 0],
            job: ['Job', 'get', {where: [['api_entity', '=', 'SK_' + ctrl.display.name,], ['api_action', '=', 'refresh']]}, 0],
          }).then(function(result) {
            ctrl.display._refresh_date = result.ref.refresh_date ? CRM.utils.formatDate(result.ref.refresh_date, null, true) : ts('never');
            if (result.job && result.job.id) {
              ctrl.display._job = result.job;
            } else {
              ctrl.display._job = defaultJobParams();
            }
          });
        }
        if (!ctrl.display.id && !ctrl.display._job) {
          ctrl.display._job = defaultJobParams();
        }
        this.parent.initColumns({label: true});
        this.display.settings.columns = this.display.settings.columns.filter((col) => this.isColumnAllowed(col.key));
      };

      // Do not allow pseudo-fields to be used as columns.
      this.isColumnAllowed = (key) => {
        return key && !CRM.crmSearchAdmin.pseudoFields.find((field) => field.name === key);
      };

      this.onChangeEntityPermission = function() {
        if (ctrl.display.settings.entity_permission.length > 1) {
          ctrl.display.settings.entity_permission_operator = ctrl.display.settings.entity_permission_operator || 'AND';
        } else {
          delete ctrl.display.settings.entity_permission_operator;
        }
      };

      function defaultJobParams() {
        return {
          parameters: 'version=4',
          is_active: false,
          run_frequency: 'Hourly',
        };
      }

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
