# Konstrukt, a tool for building CSS and JS

Konstrukt fits a toolchain around such tools as SASS, Coffeescript and Sprockets.
It's basically a very lightweight build tool for web apps.

Using Konstrukt, you can make PHP build scripts that call the underlying tools using 
an abstract interface. You may also configure your custom build script to automatically 
listen for changes in the underlying files.

## Usage:

* Add the konstrukt lib to your project;
* Create a build.php file, like the one in the examples directory;
* Customize to your liking;
* `chmod +x build.php`
* `./build.php`

Konstrukt is created and maintained by ExtendD (www.extendd.nl).