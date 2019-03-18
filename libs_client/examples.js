$(document).ready(function() {
    function client_cache_example() {
        var data = cache_fetch('example', 'client-cache', 'data');

        if (data === false) {
            console.log('No data in cache ...');

            var result = get_some_data();

            data = cache_store('example', 'client-cache', 'data', result);
        }

        data = cache_fetch('example', 'client-cache', 'data');

        if (data !== false) {
            console.log('Data in cache ...');
            console.log(data);

            cache_delete('example', 'client-cache', 'data');
        }
    }

    function get_some_data() {
        return 'Lorem Ipsum Dolor Sit Amet ...';
    }

    //client_cache_example();

    //rest_request_default();

    //rest_request_caching();

    rest_request_versioning();
});

function rest_request_default() {
    var location = 'http://localhost/caching/index.php?component=example&identifier=test-data';

    $.ajax({
        url: location
    }).done(function(response) {
        response = JSON.parse(response);
        continue_processing(response);
    });
}

function rest_request_caching() {
    var data = cache_fetch('example', 'test-data', 'data');

    if (data === false) {
        var location = 'http://localhost/caching/index.php?component=example&identifier=test-data';

        $.ajax({
            url: location
        }).done(function(response) {
            response = JSON.parse(response);
            data = cache_store('example', 'test-data', 'data', response.data);
            continue_processing(data);
        });
    } else {
        continue_processing(data);
    }
}

function rest_request_versioning() {
    var data = cache_fetch('example', 'test-data', 'data');
    var version = 0;

    if (data !== false) {
        version = data.version;
    }

    var location = 'http://localhost/caching/index.php?component=example&identifier=test-data&version='+version;

    $.ajax({
        url: location
    }).done(function(response) {
        if (response !== 'up-to-date') {
            response = JSON.parse(response);
            data = cache_store('example', 'test-data', 'data', response.data, response.version);
        }

        continue_processing(data);
    });
}
