
# Apigee Devportal Kickstart Drupal8 Drops - Pantheon

This repository is meant to be copied one-time by the the [Terminus Build Tools Plugin](https://github.com/pantheon-systems/terminus-build-tools-plugin) but can also be used as a template. It should not be cloned or forked directly.

The Terminus Build Tools plugin will scaffold a new project, including:

* Apigee devportal Kickstart on Drupal 8
* A free Pantheon sandbox site

For more details and instructions on creating a new project, see the [Terminus Build Tools Plugin](https://github.com/pantheon-systems/terminus-build-tools-plugin/).

## Important files and directories

### `/web`

Pantheon will serve the site from the `/web` subdirectory due to the configuration in `pantheon.yml`. This is necessary for a Composer based workflow. Having your website in this subdirectory also allows for tests, scripts, and other files related to your project to be stored in your repo without polluting your web document root or being web accessible from Pantheon. They may still be accessible from your version control project if it is public. See [the `pantheon.yml`](https://pantheon.io/docs/pantheon-yml/#nested-docroot) documentation for details.

#### `/config`

One of the directories moved to the git root is `/config`. This directory holds Drupal's `.yml` configuration files. In more traditional repo structure these files would live at `/sites/default/config/`. Thanks to [this line in `settings.php`](https://github.com/pantheon-systems/example-drops-8-composer/blob/54c84275cafa66c86992e5232b5e1019954e98f3/web/sites/default/settings.php#L19), the config is moved entirely outside of the web root.

### `composer.json`
This project uses Composer to manage third-party PHP dependencies.

The `require` section of `composer.json` should be used for any dependencies your web project needs, even those that might only be used on non-Live environments. All dependencies in `require` will be pushed to Pantheon.

If you are just browsing this repository on GitHub, you may not see some of the directories mentioned above. That is because Drupal core and contrib modules are installed via Composer and ignored in the `.gitignore` file.

A custom, [Composer version of Drupal 8 for Apigee kickstarter](https://github.com/apigee/devportal-kickstart-project-composer) is used as the source for Drupal core.

**Apigee Devportal Kickstart Drupal 8 profile** is included in this composer.json which allows you install the profile directly. Third party Drupal dependencies, such as contrib modules, are added to the project via `composer.json`. The `composer.lock` file keeps track of the exact version of dependency. [Composer `installer-paths`](https://getcomposer.org/doc/faqs/how-do-i-install-a-package-to-a-custom-path-for-my-framework.md#how-do-i-install-a-package-to-a-custom-path-for-my-framework-) are used to ensure the Drupal dependencies are downloaded into the appropriate directory.

Non-Drupal dependencies are downloaded to the `/vendor` directory.

## Installation
Before we begin choose a machine-friendly site name. It should be all lower case with dashes instead of spaces. I'll use `d8-composer-no-ci` but choose your own. Once you have a site name export it to a variable for re-use.

## Creating the Pantheon Site
 1. Set env. variables

  ```bash
export PANTHEON_SITE_NAME="your-portal-name"
```
You should also be authenticated with Terminus. See the  [Authenticate into Terminus](https://pantheon.io/docs/machine-tokens/#authenticate-into-terminus)  section of the  [machine tokens documentation](https://pantheon.io/docs/machine-tokens)  for details.

 2. Create a new Pantheon site with an empty upstream

  ```bash
terminus site:create $PANTHEON_SITE_NAME 'My Apigee Kickstart Dev Portal' empty
```

## Cloning this repo locally and deploying on Panthoen

 1. Clone this repository locally:
 ```bash
git clone git@github.com:stratus-meridian/apigee-kickstart-drupal8-drops.git $PANTHEON_SITE_NAME
```
This command assumes you have [SSH keys](https://pantheon.io/docs/ssh-keys/) added to your GitHub account. If you don't, you can clone the repository over HTTPS:
```bash
git clone https://github.com/stratus-meridian/apigee-kickstart-drupal8-drops.git $PANTHEON_SITE_NAME
```
 2. `cd` into the cloned directory:
 ```bash
cd $PANTHEON_SITE_NAME
```
## Updating the Git Remote URL

 1. Store the Git URL for the Pantheon site created earlier in a variable:
```bash
export PANTHEON_SITE_GIT_URL="$(terminus connection:info $PANTHEON_SITE_NAME.dev --field=git_url)"
```
 2. Update the Git remote to use the Pantheon site Git URL returned rather than the `apigee-kickstart-drupal8-drops` GitHub URL:
 ```bash
git remote set-url origin $PANTHEON_SITE_GIT_URL
 ```

### Downloading Dependencies and updates with Composer

 1. Run composer update to fetch updated code from kickstart
```bash
composer update
```
 2. And now we need to install:
 ```bash
composer install
```
 3.  Make sure new changes are showing up (if any)
```bash
git status
```
 4. Set the site to `git` mode:
 ```bash
terminus connection:set $PANTHEON_SITE_NAME.dev git
```
 5. Add and commit changes
 ```bash
git add .
git commit -m 'Drupal 8 and dependencies'
git push --force
```
A Git force push is necessary because we are writing over the empty repository on Pantheon with our new history that was started on the local machine. Subsequent pushes after this initial one should not use `--force`:

### Running Install
Now that we have all the files in Pantheon, its time to install Apigee devportal Kickstart!

 1. Set the site connection mode to `sftp`:
```bash
terminus connection:set $PANTHEON_SITE_NAME.dev sftp
```
 2. Use Terminus Drush to install Kickstart profile:
 ```bash
terminus drush $PANTHEON_SITE_NAME.dev -- site-install apigee_devportal_kickstart -y
```
 3. Log in to your new Drupal 8 site to verify it is working. You can get a one-time login link using Drush:
 ```bash
terminus drush $PANTHEON_SITE_NAME.dev -- uli
```

### Cleanup

 1. Before switching the connection mode to git, we have to commit our changes.
 ```bash
terminus env:commit $PANTHEON_SITE_NAME.dev
```
 3. Now you can change your site connection mode to git
```bash
terminus connection:set $PANTHEON_SITE_NAME.dev git
```

## Updating your site

When using this repository to manage your Drupal site, you will no longer use the Pantheon dashboard to update your Drupal version. Instead, you will manage your updates using Composer. Ensure your site is in Git mode, clone it locally, and then run composer commands from there.  Commit and push your files back up to Pantheon as usual.


### References

 - Thanks to Pantheon for detailed documentation. I adapted these
   instructions from the link below.  [Drupal 8 and Composer on Pantheon
   Without Continuous
   Integration](https://pantheon.io/docs/guides/drupal-8-composer-no-ci)
 - Composer.json file is created from [Apigee Developer portal Kickstart
   Repo](https://github.com/apigee/devportal-kickstart-project-composer)
