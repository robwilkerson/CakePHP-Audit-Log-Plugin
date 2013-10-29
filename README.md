# Audit Log Plugin

A logging plugin for [CakePHP](http://cakephp.org). The included `AuditableBehavior`  creates an audit history for each instance of a model to which it's attached.

The behavior tracks changes on two levels. It takes a snapshot of the fully hydrated object _after_ a change is complete and it also records each individual change in the case of an update action.

## Features

* Support for CakePHP 2.0. Thanks, @jasonsnider.
* Tracks object snapshots as well as individual property changes.
* Allows each revision record to be attached to a source -- usually a user -- of responsibility for the change.
* Allows developers to ignore changes to specified properties. Properties named `created`, `updated` and `modified` are ignored by default, but these values can be overwritten.
* Handles changes to HABTM associations.
* Fully compatible with the [`PolymorphicBehavior`](http://bakery.cakephp.org/articles/view/polymorphic-behavior).
* Does not require or rely on the existence of explicit models revisions (`AuditLog`) and deltas (`AuditLogDeltas`).

## Installation

### CakePHP >= 2.0

#### As an Archive  

1. Click the big ol' **Downloads** button next to the project description.
1. Extract the archive to `app/Plugin/AuditLog`.

#### As a Submodule

1. `$ git submodule add git://github.com/robwilkerson/CakePHP-Audit-Log-Plugin.git <path_to>/app/Plugin/AuditLog`
1. `$ git submodule init`
1. `$ git submodule update`

To create tables you can use schema shell. To create tables execute:

    cd <path_to>/app/
    chmod +x ./Console/cake
    ./Console/cake schema create -p AuditLog

### CakePHP 1.3.x

For use with CakePHP 1.3.x, be sure to use code from the `1.3` branch and follow the instructions in that README file.

### Next Steps

1. Run the `install.sql` file on your CakePHP application database. This will create the `audits` and `audit_deltas` tables that will store each object's relevant change history.
1. Create a `currentUser()` method, if desired.

    The `AuditableBehavior` optionally allows each changeset to be "owned" by a "source" -- typically the user responsible for the change. Since user and authentication models vary widely, the behavior supports a callback method that should return the value to be stored as the source of the change, if any.

    The `currentUser()` method must be available to every model that cares to track a source of changes, so I recommend that a copy of CakePHP's `app_model.php` file be created and the method added there. Keep it DRY, right?

	Storing the changeset source can be a little tricky if the core `Auth` component is being used since user data isn't readily available at the model layer where behaviors lie. One option is to forward that data from the controller. One means of doing this is to include the following code in `AppController::beforeFilter()`:
	
        if( !empty( $this->data ) && empty( $this->data[$this->Auth->userModel] ) ) {
          $this->data[$this->Auth->userModel] = $this->currentUser();
        }

    The behavior expects the `currentUser()` method to return an associative array with an `id` key. Continuing from the example above, the following code might appear in the `AppModel`:

        protected function currentUser() {
          $user = $this->Auth->user();
          
          return $user[$this->Auth->userModel]; # Return the complete user array
        }
  
1. Attach the behavior to any desired model and configure.

## Usage

Applying the `AuditableBehavior` to a model is essentially the same as applying any other CakePHP behavior. The behavior does offer a few configuration options:

<dl>
	<dt>`ignore`</dt>
	<dd>An array of property names to be ignored when records are created in the deltas table.</dd>
	<dt>`habtm`</dt>
	<dd>An array of models that have a HABTM relationship with the acting model and whose changes should be monitored with the model. If the HABTM model is auditable in its own right, don't include it here. This option is for related models whose changes are _only_ tracked relative to the acting model.</dd>
</dl>

### Syntax

    # Simple syntax accepting default options
    class Task extends AppModel {
      public $actsAs = array( 'AuditLog.Auditable' );
          
      # 
      # Additional model code.
      #
    }
    
    # Syntax with explicit options
    class Task extends AppModel {
      public $actsAs = array(
        'AuditLog.Auditable' => array(
          'ignore' => array( 'active', 'name', 'updated' ),
          'habtm'  => array( 'Type', 'Project' )
        )
      );
      
      # 
      # Additional model code.
      #
    }

## Limitations

* The master branch is not backwards compatible with CakePHP <=1.3.x. If you need compatibility with these version please install the code from the `1.3` branch and follow the instructions in that README. 

## License

This code is licensed under the [MIT license](http://www.opensource.org/licenses/mit-license.php).

## Notes

Feel free to submit bug reports or suggest improvements in a ticket or fork this project and improve upon it yourself. Contributions welcome.
