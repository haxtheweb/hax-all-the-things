# hax-all-the-things
A demonstration space for spinning up HAX across multiple platforms

This will seem weird, but this has copies of everything we do in the HAX capable space.
This is all the platforms (copies of them, files and all) and ddev configs ahead of time
so that you can test out HAX in different platforms.

## This repo is not intended to be used.
It is for demo'ing at events and will only be updates as needed to express what's possible in HAX. It links some things together in order to simplify things for us to demo all these different platforms.

Again, do not use this unless you're just trying to play with stuff rapidly.

## Using this
So you ignored the advise above. Great, we love people that break things!
The idea is to go into any of the platforms in the `platforms` directory and then run `ddev start` and get a working copy of that system with all the dependencies in place. You'll have to go to the modules / plugins / extensions page of each system (except HAXcms) and enable the hax / web components modules. But all the compiling has been done ahead of time and files put in the right place.

Again, do not use this for productions usage as these versions will fall out of date with other newer versions / releases of the projects in here (HAX included).