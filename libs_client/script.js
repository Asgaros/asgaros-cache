function continue_processing(data) {
    console.log(data);
}












// https://www.w3schools.com/html/html5_webstorage.asp
$(document).ready(function() {
    $('#button-rest').click(function() {
        var component = 'example';
        var identifier = 'test-data';
        var key = component+'-'+identifier;
        var version = 0;

        console.log('REST-request for: '+key);

        // Try to get data from cache.
        var data = localStorage.getItem('cached-data-'+key);

        // If data found, set version of cached data.
        if (data !== null) {
            data = JSON.parse(data);
            version = data.version;

            console.log('Cached data found on client: Version '+version);
        } else {
            console.log('No cached data found on client');
        }

        // Set location.
        var location = 'http://localhost/caching/index.php?component='+component+'&identifier='+identifier+'&version='+version;

        console.log('Send request to: '+location);

        $.ajax({
            url: location
        }).done(function(response) {
            console.log('Response received ...');

            if (response === 'up-to-date') {
                console.log('Cached data on client up-to-date.');

                console.log(data);
            } else {
                console.log('Update data on client.')

                console.log(response);

                // Parse response to JSON-object.
                data = JSON.parse(response);

                // Save data in localStorage.
                localStorage.setItem('cached-data-'+key, response);

                console.log(JSON.parse(localStorage.getItem('cached-data-'+key)));
            }
        });
    });

    // https://www.tutorialdocs.com/article/indexeddb-tutorial.html
});
