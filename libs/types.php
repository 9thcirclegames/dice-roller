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
 *	\brief		This static class makes easier static analysis on the code (PHPLint).
 *	\details	Methods return empty arrays, not NULLs - which means, gettype() returns "array"
 *				and is_array() returns TRUE.
 *				Also, static analysis is possible because those empty arrays are known to be
 *				of the declared type (e.g. string[string]).
 *				Mixed index is not supported by design.
 */
class Types
{
	// type[int]
	
	/*!
	 *	\brief		Returns an empty array which is known to be mixed[int]
	 *	@return		array		Array with numeric index.
	 */
	static public /*. mixed[int] .*/ function getMixedInt()
	{
		$arr = array(0 => null);
		unset($arr[0]);
		return $arr;
	}
	
	/*!
	 *	\brief		Returns an empty array which is known to be bool[int]
	 *	@return		array		Array bool[int].
	 */
	static public /*. bool[int] .*/ function getBoolInt()
	{
		$arr = array(0 => FALSE);
		unset($arr[0]);
		return $arr;
	}
	
	/*!
	 *	\brief		Returns an empty array which is known to be float[int]
	 *	@return		array		Array float[int].
	 */
	static public /*. float[int] .*/ function getFloatInt()
	{
		$arr = array(0 => 0.0);
		unset($arr[0]);
		return $arr;
	}
	
	/*!
	 *	\brief		Returns an empty array which is known to be int[int]
	 *	@return		array		Array int[int].
	 */
	static public /*. int[int] .*/ function getIntInt()
	{
		$arr = array(0 => 0);
		unset($arr[0]);
		return $arr;
	}
	
	/*!
	 *	\brief		Returns an empty array which is known to be object[int]
	 *	@return		array		Array object[int].
	 */
	static public /*. object[int] .*/ function getObjectInt()
	{
		$arr = array(0 => NULL);
		unset($arr[0]);
		return $arr;
	}
	
	/*!
	 *	\brief		Returns an empty array which is known to be resource[int]
	 *	@return		array		Array resource[int].
	 */
	static public /*. resource[int] .*/ function getResourceInt()
	{
		$arr = array(0 => NULL);
		unset($arr[0]);
		return $arr;
	}
	
	/*!
	 *	\brief		Returns an empty array which is known to be string[int]
	 *	@return		array		Array string[int].
	 */
	static public /*. string[int] .*/ function getStringInt()
	{
		$arr = array(0 => '');
		unset($arr[0]);
		return $arr;
	}
	
	// type[string]
	
	/*!
	 *	\brief		Returns an empty array which is known to be mixed[string]
	 *	@return		array		Array mixed[string].
	 */
	static public /*. mixed[string] .*/ function getMixedString()
	{
		$arr = array('' => NULL);
		unset($arr['']);
		return $arr;
	}
	
	/*!
	 *	\brief		Returns an empty array which is known to be bool[string]
	 *	@return		array		Array bool[string].
	 */
	static public /*. bool[string] .*/ function getBoolString()
	{
		$arr = array('' => FALSE);
		unset($arr['']);
		return $arr;
	}
	
	/*!
	 *	\brief		Returns an empty array which is known to be float[string]
	 *	@return		array		Array float[string].
	 */
	static public /*. float[string] .*/ function getFloatString()
	{
		$arr = array('' => 0.0);
		unset($arr['']);
		return $arr;
	}
	
	/*!
	 *	\brief		Returns an empty array which is known to be int[string]
	 *	@return		array		Array int[string].
	 */
	static public /*. int[string] .*/ function getIntString()
	{
		$arr = array('' => 0);
		unset($arr['']);
		return $arr;
	}
	
	/*!
	 *	\brief		Returns an empty array which is known to be object[string]
	 *	@return		array		Array object[string].
	 */
	static public /*. object[string] .*/ function getObjectString()
	{
		$arr = array('' => NULL);
		unset($arr['']);
		return $arr;
	}
	
	/*!
	 *	\brief		Returns an empty array which is known to be resource[string]
	 *	@return		array		Array resource[string].
	 */
	static public /*. resource[string] .*/ function getResourceString()
	{
		$arr = array('' => NULL);
		unset($arr['']);
		return $arr;
	}
	
	/*!
	 *	\brief		Returns an empty array which is known to be string[string]
	 *	@return		array		Hash.
	 */
	static public /*. string[string] .*/ function getStringString()
	{
		$arr = array('' => '');
		unset($arr['']);
		return $arr;
	}
}


/**
 *	\class		ValuesList
 *	\brief		An array of string values.
 *				Can be defined as as a "superstring" with e separator between its substrings,
 *				or as an array.
 *				Can be converted into e separated string, or into an array.
 */
class StringList implements \IteratorAggregate 
{
	//! Default separator to be used for strings.
	const DEFAULT_SEPARATOR = ',';
	
	
	//! Stores the values as an array.
	private /*. string[int] .*/  $values     = NULL;
	//! Default separator to be used for output strings.
	private /*. string .*/       $separator  = '';
	//! Number of elements.
	private /*. int .*/          $length     = -1;
	
	
	/**
	 *	\brief		Returns an iterator (foreach works).
	 *	@return		ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->values);
	}
	
	/**
	 *	\brief		Returns the number of elements.
	 *	@return		int
	 */
	public function getLength()
	{
		return $this->length;
	}
	
	/**
	 *	\brief		Returns an array of elements.
	 *	@return		string[int]
	 */
	public function getArray()
	{
		return $this->values;
	}
	
	/**
	 *	\brief		Converts to string with a separator.
	 *	@param		string		$separator		Separator. 
	 *							Default (NULL) is the one passed to the constructor or DEFAULT_SEPARATOR.
	 *	@return		string
	 */
	public function getString($separator = NULL)
	{
		// default params
		if ($separator === NULL) {
			$separator = $this->separator;
		}
		return implode($separator, $this->values);
	}
	
	/**
	 *	\brief		It's the same of getString, but only uses the default separator.
	 *	@return		string
	 */
	public function __toString()
	{
		$separator = $this->separator;
		return implode($separator, $this->values);
	}
	
	
	/**
	 *	\class		Constructor.
	 *				You can pass:
	 *				* An array of values which will be converted to strings.
	 *				  If array is associative, keys will be lost.
	 *				* An array and the separator which will be used to convert to string.
	 *				* A string with uses the default separator
	 *				* A string and the separator it uses.
	 *				* A non-string value which will be converted to a 1-element array, and optionally a separator.
	 *	@param		mixed		$values			A string or an array of values. See description.
	 *	@param		string		$separator		Only useful if $value is a string. See description.
	 *	@return		void
	 */
	public function __construct($values, $separator = NULL)
	{
		// init props
		$this->values     = Types::getStringInt();
		$this->separator  = self::DEFAULT_SEPARATOR;
		
		// assign vars
		/*. int .*/ $i = 0;
		
		// set separator
		if ($separator !== NULL) {
			$this->separator = $separator;
		}
		
		
		// set values
		if (is_string($values)) {
			// if $values is passed as string, convert to array
			$this->values = explode($this->separator, (string)$values);
		} elseif (is_array($values)) {
			// if $values is passed as array, convert elements to string
			foreach (/*. (mixed[]) .*/ $values as $val) { ## PHPLINT_IGNORE
				$this->values[$i] = (string)$val;
				$i++;
			}
		} else {
			/*. string[int] .*/ $this->values[0] = (string)$values;
		}
		
		$this->length = count($this->values);
	}
}


/**
 *	\class			Char
 *	\brief			1-character string. Can be empty.
 *	\warning		Only ASCII chars are safe.
 */
class Char
{
	private /*. string .*/ $chr = '';
	
	/**
	 *	\brief		Return char.
	 *	@return		string
	 */
	public function getString()
	{
		return $this->chr;
	}
	
	/**
	 *	\brief		Return char's ASCII code.
	 *	@return		int
	 */
	public function getInt()
	{
		return ord($this->chr);
	}
	
	/**
	 *	\brief		To String.
	 *	@return		string
	 */
	public function __toString()
	{
		return $this->getString();
	}
	
	/**
	 *	\brief		Constructor.
	 *	@param		mixed		$chr		Character or empty string.
	 *										Can be NULL/empty (''), int (ASCII code) or object.
	 *										No other types allowed (if not empty).
	 *										Non-ASCII chars are not safe and not tested.
	 *	@return		void
	 *	@throws		Exception
	 */
	public function __construct($chr)
	{
		// contains the new converted value from $chr
		/*. string .*/ $chrOk = '';
		
		// convert to string
		if (is_string($chr) === TRUE) {
			$chrOk = (string)$chr;
		} elseif ($chr === NULL || empty($chr) === TRUE) {
			// NULL or empty: ''
			$chrOk = '';
		} elseif (is_int($chr) === TRUE) {
			// ASCII code
			$chrOk = chr((int)$chr);
		} elseif (is_object($chr) === TRUE) {
			// __toString()
			$chrOk = (string)$chr;
		} else {
			throw new Exception('Invalid type (' . gettype($chr) . ') for argument: ' . (string)$chr);
		}
		if (strlen($chrOk) > 1) {
			throw new Exception('Passed argument longer than 1 char: ' . $chrOk);
		}
		$this->chr = $chrOk;
	}
}


/**
 *	\class		Callable
 *	\brief		Implements the pseudo-type Callable used in the PHP docs.
 *				It's an identifier (name) of an existing function/method.
 *				If does not exists, throw an exception.
 */
class Callable
{
	private /*. string .*/ $id = '';
	
	/**
	 *	\brief		Constructor.
	 *	@param		string		$id		Identifier (name) of an existing function/method.
	 *	@return		void
	 *	@throws		Exception
	 */
	public function __construct($id)
	{
		if (is_callable($id) !== TRUE) {
			throw new Exception('Passed argument is not a callable function/method: ' . $id);
		}
		$this->id = $id;
	}
	
	/**
	 *	\brief		To String.
	 *	@return		string
	 */
	public function __toString()
	{
		return $this->id;
	}
	
	/**
	 *	\brief		Call the function/method.
	 *	@return		mixed
	 */
	public function call()
	{
		return call_user_func($this->id);
	}
}


/**
 *	\class		TypeClass
 *	\brief		Identifier (name) of an existing class.
 *				If the class does not exist, throws an exception.
 */
class TypeClass
{
	private /*. string .*/ $id = '';
	
	/**
	 *	\brief		Constructor.
	 *	@param		string		$id		Identifier (name) of an existing class.
	 *	@return		void
	 *	@throws		Exception
	 */
	public function __construct($id)
	{
		if (class_exists($id) !== TRUE) {
			throw new Exception('Passed argument is not an existing class: ' . $id);
		}
		$this->id = $id;
	}
	
	/**
	 *	\brief		To String.
	 *	@return		string
	 */
	public function __toString()
	{
		return $this->id;
	}
}


/**
 *	\class		TypeConstant
 *	\brief		Identifier (name) of an existing constant.
 *				If the constant does not exist, throws an exception.
 */
class TypeConstant
{
	private /*. string .*/ $id = '';
	
	/**
	 *	\brief		Constructor.
	 *	@param		string		$id		Identifier (name) of an existing const.
	 *	@return		void
	 *	@throws		Exception
	 */
	public function __construct($id)
	{
		if (defined($id) !== TRUE) {
			throw new Exception('Passed argument is not an existing constant: ' . $id);
		}
		$this->id = $id;
	}
	
	/**
	 *	\brief		Get constant's name.
	 *	@return		string
	 */
	public function getName()
	{
		return $this->id;
	}
	
	/**
	 *	\brief		Get constant's value.
	 *	@return		mixed
	 */
	public function get()
	{
		return constant($this->id);
	}
	
	/**
	 *	\brief		To String.
	 *	@return		string
	 */
	public function __toString()
	{
		return (string)constant($this->id);
	}
}

?>