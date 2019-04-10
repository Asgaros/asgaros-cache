$ = (typeof $ === 'undefined' && typeof jQuery !== 'undefined') ? jQuery : $;

// Run certain logic as soon as the DOM-tree is ready for manipulation.
$(document).ready(function() {
    validate_cacheable_views();
    process_cacheable_views();
});

function cache_store(component, identifier, type, data, version) {
    if (typeof(Storage) === 'undefined') {
        return false;
    }

    var key = 'cached-'+type+'-'+component+'-'+identifier;

    // Create timestamp for version-parameter if no version is passed.
    if (typeof version === 'undefined') {
        version = Math.floor(new Date().getTime() / 1000);
    }

    var object = {
        'component': component,
        'identifier': identifier,
        'version': version,
        'data': data
    };

    try {
        localStorage.setItem(key, JSON.stringify(object));
    } catch(error) {
        // Cache seems to be full. Delete it.
        cache_flush();

        // Try to save the data again.
        try {
            localStorage.setItem(key, JSON.stringify(object));
        } catch(error) {
            // If still not possible to save, return false.
            return false;
        }
    }

    return object;
}

// TODO: Data only parameter
function cache_fetch(component, identifier, type) {
    if (typeof(Storage) === 'undefined') {
        return false;
    }

    var key = 'cached-'+type+'-'+component+'-'+identifier;

    data = localStorage.getItem(key);

    if (data === null) {
        return false;
    } else {
        return JSON.parse(data);
    }
}

function cache_delete(component, identifier, type) {
    if (typeof(Storage) === 'undefined') {
        return false;
    }

    var key = 'cached-'+type+'-'+component+'-'+identifier;

    localStorage.removeItem(key);

    return true;
}

function cache_flush() {
    if (typeof(Storage) === 'undefined') {
        return false;
    }

    localStorage.clear();
}

// Ensures that the announced views in cookies exist in the client-cache.
function validate_cacheable_views() {
    var changed = false;

    // Try to get an existing cookie first.
    var cached_views = Cookies.getJSON('cached_views');

    // Only continue if a cookie exists.
    if (cached_views !== undefined) {
        $.each(cached_views, function(index, object) {
            // Try to get the data from the local cache.
            var cached = cache_fetch(object.component, object.identifier, 'view');

            // Only continue if the cached data does not exist.
            if (cached === false) {
                delete cached_views[index];
                changed = true;
            }
        });
    }

    // Only update cookie if the changed-flag is set.
    if (changed === true) {
        if ($.isEmptyObject(cached_views)) {
            // If the object is empty, delete the cookie.
            Cookies.remove('cached_views');
        } else {
            // Otherwise update it.
            Cookies.set('cached_views', cached_views);
        }
    }

    // Ensure that cached views are stored inside the announcement-cookie.
    for (var i = 0; i < localStorage.length; i++) {
        if (localStorage.key(i).substring(0, 12) == 'cached-view-') {
            var key = localStorage.key(i);
            var data = JSON.parse(localStorage.getItem(key));
            announce_cacheable_view(data.component, data.identifier, data.version);
        }
    }
}

function process_cacheable_views() {
    $('div[data-type="cacheable"]').each(function() {
        var component = $(this).attr('data-component');
        var identifier = $(this).attr('data-identifier');
        var version = $(this).attr('data-version');
        var data = $(this).html();

        if (data === '') {
            data = cache_fetch(component, identifier, 'view');

            if (data === false) {
                load_cacheable_view(component, identifier, this);
            } else {
                $(this).html(data.data);
            }
        } else {
            cache_store(component, identifier, 'view', data, version);
            announce_cacheable_view(component, identifier, version);
        }
    });
}

function announce_cacheable_view(component, identifier, version) {
    var key = component+'-'+identifier;
    var cached_views = Cookies.getJSON('cached_views');

    // Initialize object if no cookie is set.
    if (cached_views === undefined) {
        cached_views = {};
    }

    // Only add the view to the cookie if it does not already exists or if the version is different.
    if (!cached_views.hasOwnProperty(key) || (cached_views.hasOwnProperty(key) && cached_views[key].version != version)) {
        cached_views[key] = {
            'component': component,
            'identifier': identifier,
            'version': version
        };

        Cookies.set('cached_views', cached_views);
    }
}

var fallback_destination = 'http://localhost/caching/index.php';

function load_cacheable_view(component, identifier, destination) {
    var location = fallback_destination+'?component='+component+'&identifier='+identifier;

    $.ajax({
        url: location
    }).done(function(response) {
        data = JSON.parse(response);
        cache_store(component, identifier, 'view', data.data, data.version);
        announce_cacheable_view(component, identifier, data.version);
        $(destination).html(data.data);
        console.log('Load view from endpoint ...');
    });
}

function cacheable_request(component, identifier, callback, location) {
    var data = cache_fetch(component, identifier, 'data');
    var version = 0;

    if (data !== false) {
        version = data.version;
    }

    location = location+'component='+component+'&identifier='+identifier+'&version='+version;

    $.ajax({
        url: location
    }).done(function(response) {
        if (response !== 'up-to-date') {
            response = JSON.parse(response);
            data = cache_store(component, identifier, 'data', response.data, response.version);
        }

        callback(data);
    });
}
