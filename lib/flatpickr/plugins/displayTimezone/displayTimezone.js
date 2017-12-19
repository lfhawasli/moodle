"use strict";
var __assign = (this && this.__assign) || Object.assign || function(t) {
    for (var s, i = 1, n = arguments.length; i < n; i++) {
        s = arguments[i];
        for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
            t[p] = s[p];
    }
    return t;
};
function displayTimezonePlugin(pluginConfig) {
    var timezoneContainer;
    return function (fp) {
        var hooks = __assign({
            onReady: function () {
                if (fp.calendarContainer === undefined)
                    return;
                // CCLE-7068 - The commented code is no longer necessary since we're forcing to server timezone now.
                var timezoneName = (pluginConfig.timezone) ? pluginConfig.timezone : "PST";
                /*
                // Get user's local timezone.
                var timezoneOffset = new Date().getTimezoneOffset();
                // JS's getTimezoneOffset is just a numerical offset (in minutes) from GMT.
                // Just hardcode American timezones to convert the timezone to text. 
                var timezoneName = "";
                switch (timezoneOffset) {
                    case 240:
                        timezoneName = "AST";
                        break;
                    case 300:
                        timezoneName = "EST";
                        break;
                    case 360:
                        timezoneName = "CST";
                        break;
                    case 420:
                        timezoneName = "MST";
                        break;
                    case 480:
                        timezoneName = "PST";
                        break;
                    case 540:
                        timezoneName = "AKST";
                        break;
                    case 600:
                        timezoneName = "HST";
                        break;
                    default:
                        timezoneName = "GMT+" + -(timezoneOffset/60); // Fallback on GMT.
                }
                */
                timezoneContainer = fp._createElement("span", "flatpickr-timezone ", "Timezone: " + timezoneName);
                timezoneContainer.tabIndex = -2;
                fp.calendarContainer.appendChild(timezoneContainer);
            }}, {});
        return hooks;
    };
}
