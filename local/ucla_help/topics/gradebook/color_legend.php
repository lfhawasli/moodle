<?php
// This file is part of the UCLA local help plugin for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

global $CFG;

$buffer = <<<END

        <h2 class="ui header dividing">
            Gradebook at a glance
        </h2>
        
        <div class ="ui raised segment">
            <div class="ui black ribbon label">
                Color legend
            </div>
            <div class="ui divided list">
                <div class="item">
                    <div class="gradebook-column-color gradebook-color"></div> Column highlight
                </div>
                <div class="item">
                    <div class="gradebook-row-color gradebook-color"></div> Row highlight
                </div>
                <div class="item">
                    <div class="gradebook-overridden gradebook-color"></div> Overriden grade
                </div>
            </div>
            <div class="ui black ribbon label">
                Gradebook icons
            </div>
            <div class="ui divided list">
                <div class="item">
                    <img src="$CFG->wwwroot/theme/image.php/uclashared/core/1393450918/t/grades" title="Grades for student" class="smallicon" alt="Grades for student"> Grades for student
                </div>
                <div class="item">

                    <img alt="Edit grade" class="smallicon" title="Edit grade" src="$CFG->wwwroot/theme/image.php/uclashared/core/1393450918/t/edit"> Edit grade

                </div>
            </div>
            <div class="ui black ribbon label">
                 Column View Icons 
            </div>
            <div class="ui divided list">
                <div class="item">
                    <img alt="Aggregates only" class="smallicon" title="Aggregates only" src="$CFG->wwwroot/theme/image.php/uclashared/core/1393450918/t/switch_minus"> Aggregates only
                </div>
                <div class="item">
                    <img alt="Grades only" class="smallicon" title="Grades only" src="$CFG->wwwroot/theme/image.php/uclashared/core/1393450918/t/switch_plus"> Grades only
                </div>
                <div class="item">
                    <img alt="Full view" class="smallicon" title="Full view" src="$CFG->wwwroot/theme/image.php/uclashared/core/1393450918/t/switch_whole"> Full view
                </div>
            </div>
        </div>
        
END;

return $buffer;