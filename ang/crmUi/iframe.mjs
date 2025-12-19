const $ = window.CRM.$;

// Display an HTML blurb inside an IFRAME.
// example: <iframe crm-ui-iframe="getHtmlContent()"></iframe>
// example:  <iframe crm-ui-iframe crm-ui-iframe-src="getUrl()"></iframe>

function crmUiIframe() {
  return {
    scope: {
      crmUiIframeSrc: '@', // expression which evaluates to a URL
      crmUiIframe: '@' // expression which evaluates to HTML content
    },
    link: function (scope, elm, attrs) {
      const iframe = $(elm)[0];
      iframe.setAttribute('width', '100%');
      iframe.setAttribute('height', '250px');
      iframe.setAttribute('frameborder', '0');

      const refresh = function () {
        if (attrs.crmUiIframeSrc) {
          iframe.setAttribute('src', scope.$parent.$eval(attrs.crmUiIframeSrc));
        }
        else {
          let iframeHtml = scope.$parent.$eval(attrs.crmUiIframe);

          let doc = iframe.document;
          if (iframe.contentDocument) {
            doc = iframe.contentDocument;
          }
          else if (iframe.contentWindow) {
            doc = iframe.contentWindow.document;
          }

          doc.open();
          doc.writeln(iframeHtml);
          doc.close();
        }
      };

      // If the iframe is in a dialog, respond to resize events
      $(elm).parent().on('dialogresize dialogopen', function(e, ui) {
        $(this).css({padding: '0', margin: '0', overflow: 'hidden'});
        iframe.setAttribute('height', '' + $(this).innerHeight() + 'px');
      });

      $(elm).parent().on('dialogresize', function(e, ui) {
        iframe.setAttribute('class', 'resized');
      });

      scope.$parent.$watch(attrs.crmUiIframe, refresh);
    }
  };
}

export default crmUiIframe;
