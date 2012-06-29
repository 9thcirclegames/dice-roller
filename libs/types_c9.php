<?php

/*
	This file is part of PbF.
	
	You can redistribute this program and/or modify it under the terms 
	of the GNU Affero General Public License as published by
	the Free Software Foundation, version 3 of the License.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU Affero General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/



namespace C9;



/**
 *	\brief		This is very similar to Types, but contains types that are only used by C9.
 *				This makes Types portable into other projects.
 */
class TypesC9
{
	/*!
	 *	\brief		Returns an empty array which is known to be AtomValidateRule[int]
	 *	@return		array		Hash.
	 */
	static public /*. AtomValidateRule[int] .*/ function getAtomValidateRuleInt()
	{
		$arr = array(0 => NULL);
		unset($arr[0]);
		return $arr;
	}
}

?>