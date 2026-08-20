# Delete incomplete submissions

This plugin removes, in a single reviewed and confirmed operation, every incomplete submission that has been left inactive beyond a threshold you choose.

Editors set an inactivity threshold in days, generate a preview listing every submission that qualifies, and confirm once to delete the whole list. Nothing runs automatically: submissions are only removed when the action is performed.

## Safety rules

A submission is eligible only when **all** of the following hold:

- it is still queued and incomplete;
- it has been inactive for longer than the configured threshold;
- it has no published or scheduled publication;
- it has no DOI assigned to any of its publications or galleys.

Every condition is checked when the preview is generated **and** again, inside a transaction, immediately before each deletion. Only the submissions shown in the preview are considered.

The preview expires after 15 minutes, when the threshold changes, or when a newer preview is generated for the same journal or preprint server.

# Compatibility

This plugin is compatible with the following PKP applications:

- OJS 3.5.0
- OPS 3.5.0

Support for OJS/OPS 3.4.0 is kept on the `stable-3_4_0` branch.

# Installation

Download the `deleteIncompleteSubmissions.tar.gz` package under the '[Releases](https://github.com/lepidus/deleteIncompleteSubmissions/releases)' tab of this repository.

Upload the package in the `Website > Plugins` section of the Dashboard.

# Usage

After installing the plugin, it will appear in the plugins list, from there, activate the plugin, then expand the options in the arrow, and click on the ***`Delete submissions...`*** action:

![](screenshots/plugin-options.png)

From the open modal, set the inactivity threshold in days and generate a preview containing the ID, title, status, and last activity date of every eligible submission:

![](screenshots/delete-submissions-modal.png)

Review the list, then confirm to delete all of the listed submissions at once. Deletion is permanent.

# Known limitations

- The preview is held in the editor's session, so confirming from a different browser or after a session expires requires generating a new preview.
- Deletion outcomes are recorded in the PHP error log, not in the application's editorial log.

# License

This plugin is licensed under the GNU General Public License v3. Read the complete [LICENSE file](LICENSE).

*Copyright (c) 2024 Lepidus Tecnologia*
