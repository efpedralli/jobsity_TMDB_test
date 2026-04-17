Jobsity Technical Test

Performed by Eduardo Pedralli

Overview

This project was developed as part of the technical assessment for a position at Jobsity.
Below is a summary of the implementation decisions, architecture, and a few important observations found during development and testing.

How to Run the Project
Option A — Using the Database Dump (Recommended)

1. Import the database dump located at database/jobsity_test.sql
2. Configure your wp-config.php with your local database credentials.
3. Update the site URL if necessary (via database or wp-config.php).
4. Make sure the following are active:
    Theme: jobsity_test
    Plugin: TMDB_sync

Option B — Fresh WordPress Installation
1. Install a clean WordPress instance on your preferred server and database.
2. Install and activate the following:
    Advanced Custom Fields (ACF) plugin
    Import the ACF field group JSON files (located in the project, if available).
3. Upload and activate:
    Theme: jobsity_test
    Plugin: TMDB_sync
4. Use the plugin dashboard to sync movies and actors from TMDB.

Additional Notes
Some features depend on external data from the TMDB API, so initial sync is required.
Email functionality (registration/login emails) may not work in localhost environments without proper mail configuration.


Development Approach and Technical Decisions
Backend

The backend layer was implemented as a custom WordPress plugin called TMDB_sync.
The plugin is responsible for organizing and handling the data integration with the TMDB API, as well as registering the content structure used by the website.
Its responsibilities include:
orchestrating the sync process and rendering the plugin area in the WordPress dashboard;
registering the custom post types for Movies and Actors;
registering the Genre taxonomy;
centralizing API calls in tmdb_api.php to reduce duplicated code and keep the integration layer cleaner;
centralizing reusable image logic in register_image_as_thumb.php to avoid repetition.
Sync Logic
When the Sync Movies action is triggered, the plugin requests one page of upcoming movie data from TMDB. During this process, it also retrieves cast information in order to keep actor content populated.
When the Sync Actors action is triggered, the plugin requests one page of popular actors from TMDB. During this process, it also retrieves related movies to help populate the movie catalog.
The plugin stores all required data for movies, actors, genres, and their relationships.

Additional Notes About the Backend
Some ACF fields were used in this project to demonstrate familiarity with plugins and hooks in the WordPress ecosystem.
In a real production project, I would typically choose either a more code-driven custom fields approach or a plugin-based field management approach, instead of mixing both, unless there were a clear reason to do so.

Frontend

The frontend was implemented as a custom WordPress theme called jobsity_test.
The theme follows standard WordPress template conventions and includes the required pages and detail views.
Main Implemented Templates
index.php displays:
the upcoming movies section,
and the most popular actors section;
header.php includes:
a simple navigation menu,
and the search form;
footer.php contains:
footer structure,
script loading,
and the current year output;
archive-movie.php and archive-actor.php render the movie and actor listing pages;
single-movie.php and single-actor.php render the detail pages for each movie and actor;
page-wishlist.php renders the wishlist page for authenticated users.
Search and Wishlist

The project also includes the bonus features:

Search
A global search form was added through searchform.php;
Search results are rendered in search.php;
The sorting logic follows the requested custom formula from the test instructions.
Wishlist
Wishlist functionality is available across the site through AJAX buttons displayed below movie items;
Saved items are shown in page-wishlist.php;
The feature requires authentication;
Login and registration use the native WordPress authentication flow.
Observations and Limitations Found During Testing
API Data Limitations

A few behaviors are related to the data currently returned by TMDB rather than to implementation issues.

At the present date, only a very small number of movies are classified as upcoming in the API response. Most available movies have release dates earlier than April 17, 2026, so the homepage may show fewer upcoming movies than expected. This is a data availability issue rather than a code issue. In the actor gallery, many actors do not have multiple profile images available in the TMDB API. The logic to fetch and display up to 10 images is implemented, but in many cases the API simply does not provide enough image entries.
Local Environment Limitation

Because the project was tested in a local environment, the full email flow for registration/login confirmation could not be fully validated.

However:

the registration flow works;
the user is correctly created in the WordPress database as a Subscriber.

So the limitation is related to local mail delivery, not user creation itself.

Final Notes

This project was built with the goal of keeping the architecture clean, readable, and aligned with standard WordPress development practices.

The main focus was to:

separate backend and frontend responsibilities clearly;
keep the plugin responsible for data integration and content modeling;
keep the theme responsible for presentation and user interaction;
implement the requested features in a practical and maintainable way.