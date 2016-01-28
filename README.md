# Audit Log Plugin

## 3.x Development Branch

The `dev-3.x` branch is now dedicated to any CakePHP 3.x development. It is work in progress and NOT FUNCTIONAL yet.
Feel free to submit bug reports or suggest improvements in a ticket or fork this project and improve upon it yourself. Contributions welcome!


## Installation

### Download the Archive

1. Click the big ol' **Downloads** button next to the project description.
1. Extract the archive to `<path_to_project>/plugins/AuditLog`.

Or alternatively use git to clone this branch in the appropriate path


### Setup Database

To create tables you can use migrations. To create tables execute:

    cd <path_to_project>
    bin/cake migrations migrate -p AuditLog

This will create the `audits` and `audit_deltas` tables that will store each object's relevant change history.

If you are not using Migrations: Directly execute the SQL-script `AuditLog/src/scripts/schema.sql`

### Load the Plugin

in `config/bootstrap.php` add the line `Plugin::load('AuditLog');`

### Add the Behavior to an existing Table

Applying the `AuditableBehavior` to a Table is essentially the same as applying any other CakePHP behavior. 

Open a table (e.g. `src/Model/Table/UsersTable.php`) and add the line 

        $this->addBehavior('AuditLog.Auditable');

to the initialize function. See [Behaviors](http://book.cakephp.org/3.0/en/orm/behaviors.html) in the official CakePHP documentation for more details.


## License

This code is licensed under the [MIT license](http://www.opensource.org/licenses/mit-license.php).
