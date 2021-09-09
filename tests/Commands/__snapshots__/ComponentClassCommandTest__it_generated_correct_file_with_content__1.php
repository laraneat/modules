<?php return '<?php
namespace App\\Modules\\Blog\\View\\Component;
use Illuminate\\View\\Component;
class Blog extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \\Illuminate\\View\\View|string
     */
    public function render()
    {
        return view(\'blog::components.blog\');
    }
}
';
