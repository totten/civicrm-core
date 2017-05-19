(function (angular, $, _) {
  // A variant of angular.module() which uses a dependency list provided by the server.
  angular.crmDepends = function crmDepends(name) {
    return angular.module(name, CRM.angular.requires[name] || []);
  };
})(angular, CRM.$, CRM._);
