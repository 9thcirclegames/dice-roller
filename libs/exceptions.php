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
 *	\class		C9Exception
 *	\brief		Generic exception for uncatched errors.
 *				This Exceptions should not be thrown, use subclasses.
 */
class C9Exception extends \Exception
{
	//! Error code
	const ERR_CODE     = 10000;
	//! Error code
	const ERR_MESSAGE  = 'An error occurred';
	
	
	/**
	 *	\brief		Constructor.
	 *	@param		string		$subsys			In which subsystem the error occurred?
	 *	@param		string		$message		Error message.
	 *	@return		void
	 */
	public function __construct($subsys, $message)
	{
		if (strlen($subsys) > 0) {
			$message = 'Subsystem ' . $subsys . ': ' . static::ERR_MESSAGE;
		}
		
		parent::__construct($message, static::ERR_CODE);
	}
}


/**
 *	\class		C9Exception
 *	\brief		Fatal error. Normally, you don't catch this exception.
 */
/*. unchecked .*/ class C9ExceptionCode extends C9Exception
{
	const ERR_CODE     = 10100;
	const ERR_MESSAGE  = 'Syntax Error';
	
	
	/**
	 *	\brief		Constructor.
	 *	@param		string		$subsys			In which subsystem the error occurred?
	 *	@param		string		$message		Error message.
	 *	@return		void
	 */
	public function __construct($subsys, $message)
	{
		parent::__construct($subsys, $message);
	}
}


/**
 *	\class		C9ExceptionValidation
 *	\brief		Some data does not validate.
 */
class C9ExceptionValidation extends C9Exception
{
	const ERR_CODE     = 10200;
	const ERR_MESSAGE  = 'Syntax Error';
	
	
	/**
	 *	\brief		Constructor.
	 *	@param		string		$subsys			In which subsystem the error occurred?
	 *	@param		string		$message		Error message.
	 *	@return		void
	 */
	public function __construct($subsys, $message)
	{
		// default is validation
		if (strlen($subsys) < 1) {
			$subsys = 'Validation';
		}
		
		parent::__construct($subsys, $message);
	}
}

?>