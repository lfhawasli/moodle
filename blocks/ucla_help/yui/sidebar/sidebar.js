/**
 * UCLA Help sidebar
 * 
 * Module to register other widgets that might interact with the sidebar and 
 * require notifications.
 * 
 */

YUI.add('moodle-block_ucla_help-sidebar', function(Y) {
    
    var ModulenameNAME = 'ucla_sidebar';
    
    var SIDEBAR = function() {
        SIDEBAR.superclass.constructor.apply(this, arguments);
    };
    
    Y.extend(SIDEBAR, Y.Base, {
        registermodules : [],
        
        initializer : function(config) { 
            // Nothing to do here..
        },
        
        register_module : function(object) {
            this.registermodules.push(object);
        },

        invoke_function : function(functionname, args) {
            for (module in this.registermodules) {
                if (functionname in this.registermodules[module]) {
                    this.registermodules[module][functionname](args);
                }
            }
        }
    }, {
        NAME : ModulenameNAME, 
        
        ATTRS : {
                 aparam : {}
        } 
    });

    M.block_ucla_help = M.block_ucla_help || {}; // This line use existing name path if it exists, otherwise create a new one. 
    M.block_ucla_help.sidebar = M.block_ucla_help.sidebar || new SIDEBAR();
    
    M.block_ucla_help.init_sidebar = function(config) { 
        // Nothing to do here... yet
    };
  }, '@VERSION@', {
      requires:['base']
  });