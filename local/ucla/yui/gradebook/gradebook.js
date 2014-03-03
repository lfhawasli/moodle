/**
 * YUI script to copy the user email & ID column data to the 'user' cell
 * 
 */


YUI().use('node', 'event', function(Y) {

    Y.on('domready', function() {

        var gradebook = Y.one('.path-grade-report');
        
        if (gradebook) {
            
            // Grab all the 'user' cells
            var headings = Y.all('#user-grades tbody tr');
            
            // For each cell, grab the corresponding 'email' and 'id' cells
            // and copy their contents over.
            headings.each(function(node) {
                var user = node.one('.user');

                if (user) {
                    var idnumber = node.one('.useridnumber');
                    var email = node.one('.useremail');
                    
                    user.append(Y.Node.create('<span class="idnumber">' + idnumber.get('text') + '</span>'));
                    user.append(Y.Node.create('<span class="email">' + email.get('text') + '</span>'));
                    idnumber.setStyle('display', 'none');
                    email.setStyle('display', 'none');
                } 

            });
        }
    });
    
});

