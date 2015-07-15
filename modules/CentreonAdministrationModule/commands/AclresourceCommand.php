<?php
/*
 * Copyright 2005-2015 CENTREON
 * Centreon is developped by : Julien Mathis and Romain Le Merlus under
 * GPL Licence 2.0.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation ; either version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, see <http://www.gnu.org/licenses>.
 *
 * Linking this program statically or dynamically with other modules is making a
 * combined work based on this program. Thus, the terms and conditions of the GNU
 * General Public License cover the whole combination.
 *
 * As a special exception, the copyright holders of this program give CENTREON
 * permission to link this program with independent modules to produce an executable,
 * regardless of the license terms of these independent modules, and to copy and
 * distribute the resulting executable under terms of CENTREON choice, provided that
 * CENTREON also meet, for each linked independent module, the terms  and conditions
 * of the license of that module. An independent module is a module which is not
 * derived from this program. If you modify this program, you may extend this
 * exception to your version of the program, but you are not obliged to do so. If you
 * do not wish to do so, delete this exception statement from your version.
 *
 * For more information : contact@centreon.com
 *
 */
namespace CentreonAdministration\Commands;

use Centreon\Api\Internal\BasicCrudCommand;
use Centreon\Internal\Di;
use CentreonAdministration\Events\aclTagsEvent;
/**
 * 
 */
class AclresourceCommand extends BasicCrudCommand
{
    /**
     *
     * @var type 
     */
    public $objectName = 'aclresource';
    
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * @cmdForm /centreon-administration/aclresource/update required
     * @cmdParam boolean|true all-hosts required all host 
     * @cmdParam boolean|true all-bas required all bas 
     */ 
    public function createAction($params) {
        
        $events = Di::getDefault()->get('events');
        $aclTagsEvent = new aclTagsEvent($params);
        $events->emit('centreon-administration.acl.tag', array($aclTagsEvent));
        $params = $aclTagsEvent->getParams();
        parent::createAction($params);
    }
    
    /**
     * @cmdForm /centreon-administration/aclresource/update map
     * @cmdObject string aclresource the acl resource
     */
    public function showAction($object, $fields = null, $linkedObject = '') 
    {
        parent::showAction($object, $fields, $linkedObject);
    }
    
    /**
     * 
     * @cmdForm /centreon-administration/aclresource/update optional
     * @cmdObject string aclresource the acl resource
     * @cmdParam boolean|true all-hosts optional all host 
     * @cmdParam boolean|true all-bas optional all bas 
     * @cmdParam boolean|false no-hosts optional no host 
     * @cmdParam boolean|false no-bas optional no bas 
     */
    public function updateAction($object, $params) 
    {
        $events = Di::getDefault()->get('events');
        $aclTagsEvent = new aclTagsEvent($params);
        $events->emit('centreon-administration.acl.tag', array($aclTagsEvent));
        $params = $aclTagsEvent->getParams();
        parent::updateAction($object, $params);
    }
    
    /**
     * @cmdObject string aclresource the acl resource
     */
    public function deleteAction($object) 
    {
        parent::deleteAction($object);
    }
    
}