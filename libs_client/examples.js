// https://www.w3schools.com/html/html5_webstorage.asp
$(document).ready(function() {
    $('#button-rest').click(function() {
        rest_request_versioning();
    });

    // https://www.tutorialdocs.com/article/indexeddb-tutorial.html
});

function continue_processing(data) {
    console.log(data);
}

function get_some_data() {
    return 'Lorem Ipsum Dolor Sit Amet ...';
}

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

function rest_request_default() {
    var location = 'http://localhost/wordpress/wp-content/plugins/asgaros-cache/index.php?component=example&identifier=test-data';

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
        var location = 'http://localhost/wordpress/wp-content/plugins/asgaros-cache/index.php?component=example&identifier=test-data';

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

    var location = 'http://localhost/wordpress/wp-content/plugins/asgaros-cache/index.php?component=example&identifier=test-data&version='+version;

    $.ajax({
        url: location
    }).done(function(response) {
        continue_processing('Response:');
        continue_processing(response);

        if (response !== 'up-to-date') {
            response = JSON.parse(response);
            data = cache_store('example', 'test-data', 'data', response.data, response.version);
        }

        continue_processing('Cached Data:');
        continue_processing(data);
    });
}
