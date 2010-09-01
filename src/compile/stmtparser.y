%name PC_
%declare_class {class PC_Compile_StmtParser}

%syntax_error {
    echo "Syntax Error " . ($this->state->get_file() ? "in file " . $this->state->get_file() : '');
		echo "on line " . $this->state->get_line() . ": token '" . htmlspecialchars($this->state->get_value()) . "'";
		echo " (".token_name($this->state->get_token()).") while parsing rule: ";
    foreach ($this->yystack as $entry) {
        echo $this->tokenName($entry->major) . '->';
    }
    foreach ($this->yy_get_expected_tokens($yymajor) as $token) {
        $expect[] = self::$yyTokenName[$token];
    }
	echo "\n";	
    throw new Exception('Unexpected ' . $this->tokenName($yymajor) . '(' . $TOKEN. '), expected one of: ' . implode(',', $expect));
}

%include_class {
		// current state, i.e. the function, class and variables in scope
		private $state;
		
		public $transTable = array();

    function __construct($state)
    {
				$this->state = $state;
				if(!count($this->transTable))
				{
					$start = 240; // start nice and low to be sure
					while(token_name($start) == 'UNKNOWN')
						$start++;
					$hash = array_flip(self::$yyTokenName);
					$map = array(
						ord(',') => self::COMMA,
						ord('=') => self::EQUALS,
						ord('?') => self::QUESTION,
						ord(':') => self::COLON,
						ord('|') => self::BAR,
						ord('^') => self::CARAT,
						ord('&') => self::AMPERSAND,
						ord('<') => self::LESSTHAN,
						ord('>') => self::GREATERTHAN,
						ord('+') => self::PLUS,
						ord('-') => self::MINUS,
						ord('.') => self::DOT,
						ord('*') => self::TIMES,
						ord('/') => self::DIVIDE,
						ord('%') => self::PERCENT,
						ord('!') => self::EXCLAM,
						ord('~') => self::TILDE,
						ord('@') => self::AT,
						ord('[') => self::LBRACKET,
						ord('(') => self::LPAREN,
						ord(')') => self::RPAREN,
						ord(';') => self::SEMI,
						ord('{') => self::LCURLY,
						ord('}') => self::RCURLY,
						ord('`') => self::BACKQUOTE,
						ord('$') => self::DOLLAR,
						ord(']') => self::RBRACKET,
						ord('"') => self::DOUBLEQUOTE,
						ord("'") => self::SINGLEQUOTE,
					);
					for($i = $start; $i < self::YYERRORSYMBOL + $start; $i++)
					{
						$lt = token_name($i);
      			$lt = ($lt == 'T_ML_COMMENT') ? 'T_COMMENT' : $lt;
						$lt = ($lt == 'T_DOUBLE_COLON') ?  'T_PAAMAYIM_NEKUDOTAYIM' : $lt;
						if(!isset($hash[$lt]))
							continue;
		
						$map[$i] = $hash[$lt];
					}
					$this->transTable = $map;
				}
    }
}

%left T_INCLUDE T_INCLUDE_ONCE T_EVAL T_REQUIRE T_REQUIRE_ONCE.
%left COMMA.
%left T_LOGICAL_OR.
%left T_LOGICAL_XOR.
%left T_LOGICAL_AND.
%right T_PRINT.
%left EQUALS T_PLUS_EQUAL T_MINUS_EQUAL T_MUL_EQUAL T_DIV_EQUAL T_CONCAT_EQUAL T_MOD_EQUAL T_AND_EQUAL T_OR_EQUAL T_XOR_EQUAL T_SL_EQUAL T_SR_EQUAL.
%left QUESTION COLON.
%left T_BOOLEAN_OR.
%left T_BOOLEAN_AND.
%left BAR.
%left CARAT.
%left AMPERSAND.
%nonassoc T_IS_EQUAL T_IS_NOT_EQUAL T_IS_IDENTICAL T_IS_NOT_IDENTICAL.
%nonassoc LESSTHAN T_IS_SMALLER_OR_EQUAL GREATERTHAN T_IS_GREATER_OR_EQUAL.
%left T_SL T_SR.
%left PLUS MINUS DOT.
%left TIMES DIVIDE PERCENT.
%right EXCLAM.
%nonassoc T_INSTANCEOF.
%right TILDE T_INC T_DEC T_INT_CAST T_DOUBLE_CAST T_STRING_CAST T_ARRAY_CAST T_OBJECT_CAST T_BOOL_CAST T_UNSET_CAST AT.
%right LBRACKET.
%nonassoc T_NEW T_CLONE.
%left T_ELSEIF.
%left T_ELSE.
%left T_ENDIF.
%right T_STATIC T_ABSTRACT T_FINAL T_PRIVATE T_PROTECTED T_PUBLIC.

start ::= top_statement_list.

top_statement_list ::= top_statement_list top_statement.
top_statement_list ::= .

top_statement ::= statement.
top_statement ::= function_declaration_statement.
top_statement ::= class_declaration_statement.
top_statement ::= T_HALT_COMPILER LPAREN RPAREN SEMI.

statement ::= unticked_statement.

unticked_statement ::= LCURLY inner_statement_list RCURLY.
unticked_statement ::= T_IF LPAREN expr RPAREN statement elseif_list else_single.
unticked_statement ::= T_IF LPAREN expr RPAREN COLON inner_statement_list new_elseif_list new_else_single T_ENDIF COLON.
unticked_statement ::= T_WHILE LPAREN expr RPAREN while_statement.
unticked_statement ::= T_DO statement T_WHILE LPAREN expr RPAREN SEMI.
unticked_statement ::= T_FOR 
			LPAREN
				for_expr
			SEMI 
				for_expr
			SEMI
				for_expr
			RPAREN
			for_statement.
unticked_statement ::= T_SWITCH LPAREN expr RPAREN switch_case_list.
unticked_statement ::= T_BREAK SEMI.
unticked_statement ::= T_BREAK expr SEMI.
unticked_statement ::= T_CONTINUE SEMI.
unticked_statement ::= T_CONTINUE expr SEMI.
unticked_statement ::= T_RETURN SEMI.
unticked_statement ::= T_RETURN expr_without_variable SEMI.
unticked_statement ::= T_RETURN variable SEMI.
unticked_statement ::= T_GLOBAL global_var_list SEMI.
unticked_statement ::= T_STATIC static_var_list SEMI.
unticked_statement ::= T_ECHO echo_expr_list SEMI.
unticked_statement ::= T_INLINE_HTML.
unticked_statement ::= expr SEMI.
unticked_statement ::= T_USE use_filename SEMI.
unticked_statement ::= T_UNSET LPAREN unset_variables LPAREN SEMI.
unticked_statement ::= T_FOREACH LPAREN variable T_AS 
		foreach_variable foreach_optional_arg RPAREN
		foreach_statement.
unticked_statement ::= T_FOREACH LPAREN expr_without_variable T_AS 
		w_variable foreach_optional_arg RPAREN
		foreach_statement.
unticked_statement ::= T_DECLARE LPAREN declare_list RPAREN declare_statement.
unticked_statement ::= SEMI.
unticked_statement ::= T_TRY LCURLY inner_statement_list RCURLY
		T_CATCH LPAREN
		fully_qualified_class_name
		T_VARIABLE RPAREN
		LCURLY inner_statement_list RCURLY
		additional_catches.
unticked_statement ::= T_THROW expr SEMI.

additional_catches ::= non_empty_additional_catches.
additional_catches ::= .

non_empty_additional_catches ::= additional_catch.
non_empty_additional_catches ::= non_empty_additional_catches additional_catch.

additional_catch ::= T_CATCH LPAREN fully_qualified_class_name T_VARIABLE RPAREN LCURLY inner_statement_list RCURLY.

inner_statement_list ::= inner_statement_list inner_statement.
inner_statement_list ::= .

inner_statement ::= statement.
inner_statement ::= function_declaration_statement.
inner_statement ::= class_declaration_statement.
inner_statement ::= T_HALT_COMPILER LPAREN RPAREN SEMI.

function_declaration_statement ::= unticked_function_declaration_statement.

class_declaration_statement ::= unticked_class_declaration_statement.

unticked_function_declaration_statement ::=
		T_FUNCTION is_reference T_STRING LPAREN parameter_list RPAREN
		LCURLY inner_statement_list RCURLY. {
	$this->state->end_function();
}

unticked_class_declaration_statement ::=
		class_entry_type T_STRING(C) extends_from
			implements_list
			LCURLY
				class_statement_list
			RCURLY. {
	$this->state->end_class();
}
unticked_class_declaration_statement ::=
		interface_entry T_STRING
			interface_extends_list
			LCURLY
				class_statement_list
			RCURLY. {
	$this->state->end_class();
}

class_entry_type ::= T_CLASS.
class_entry_type ::= T_ABSTRACT T_CLASS.
class_entry_type ::= T_FINAL T_CLASS.

extends_from ::= T_EXTENDS fully_qualified_class_name.
extends_from ::= .

interface_entry ::= T_INTERFACE.

interface_extends_list ::= T_EXTENDS interface_list.
interface_extends_list ::= .

implements_list ::= .
implements_list ::= T_IMPLEMENTS interface_list.

interface_list ::= fully_qualified_class_name.
interface_list ::= interface_list COMMA fully_qualified_class_name.

expr(A) ::= r_variable(var). { A = var->get_type(); }
expr(A) ::= expr_without_variable(e). { A = e; }

expr_without_variable ::= T_LIST LPAREN assignment_list RPAREN EQUALS expr.
expr_without_variable(A) ::= variable(var) EQUALS expr(e). {
	A = $this->state->set_var(var,e);
}
expr_without_variable(A) ::= variable(var) EQUALS AMPERSAND variable(e). {
	A = $this->state->set_var(var,e);
}
expr_without_variable(A) ::= variable(var) EQUALS AMPERSAND T_NEW class_name_reference(name) ctor_arguments. {
	A = $this->state->set_var(var,new PC_Obj_Type(PC_Obj_Type::OBJECT,null,name));
}
expr_without_variable ::= T_NEW class_name_reference(name) ctor_arguments(args). {
	// TODO
	$this->state->add_call(name.'::__construct',args);
}
expr_without_variable(A) ::= T_CLONE expr(e). {
	A = clone e;
}
expr_without_variable(A) ::= variable(var) T_PLUS_EQUAL expr(e). {
	A = $this->state->handle_bin_assign_op('+',var,e);
}
expr_without_variable(A) ::= variable(var) T_MINUS_EQUAL expr(e). {
	A = $this->state->handle_bin_assign_op('-',var,e);
}
expr_without_variable(A) ::= variable(var) T_MUL_EQUAL expr(e). {
	A = $this->state->handle_bin_assign_op('*',var,e);
}
expr_without_variable(A) ::= variable(var) T_DIV_EQUAL expr(e). {
	A = $this->state->handle_bin_assign_op('/',var,e);
}
expr_without_variable(A) ::= variable(var) T_CONCAT_EQUAL expr(e). {
	A = $this->state->handle_bin_assign_op('.',var,e);
}
expr_without_variable(A) ::= variable(var) T_MOD_EQUAL expr(e). {
	A = $this->state->handle_bin_assign_op('%',var,e);
}
expr_without_variable(A) ::= variable(var) T_AND_EQUAL expr(e). {
	A = $this->state->handle_bin_assign_op('&',var,e);
}
expr_without_variable(A) ::= variable(var) T_OR_EQUAL expr(e). {
	A = $this->state->handle_bin_assign_op('|',var,e);
}
expr_without_variable(A) ::= variable(var) T_XOR_EQUAL expr(e). {
	A = $this->state->handle_bin_assign_op('^',var,e);
}
expr_without_variable(A) ::= variable(var) T_SL_EQUAL expr(e). {
	A = $this->state->handle_bin_assign_op('<<',var,e);
}
expr_without_variable(A) ::= variable(var) T_SR_EQUAL expr(e). {
	A = $this->state->handle_bin_assign_op('>>',var,e);
}
expr_without_variable(A) ::= rw_variable(var) T_INC. {
	A = $this->state->handle_post_op('+',var);
}
expr_without_variable(A) ::= T_INC rw_variable(var). {
	A = $this->state->handle_pre_op('+',var);
}
expr_without_variable(A) ::= rw_variable(var) T_DEC. {
	A = $this->state->handle_post_op('-',var);
}
expr_without_variable(A) ::= T_DEC rw_variable(var). {
	A = $this->state->handle_pre_op('-',var);
}
expr_without_variable(A) ::= expr(e1) T_BOOLEAN_OR expr(e2). {
	A = $this->state->handle_bin_op('|',e1,e2);
}
expr_without_variable(A) ::= expr(e1) T_BOOLEAN_AND expr(e2). {
	A = $this->state->handle_bin_op('&',e1,e2);
}
expr_without_variable(A) ::= expr(e1) T_LOGICAL_OR expr(e2). {
	A = $this->state->handle_bin_op('||',e1,e2);
}
expr_without_variable(A) ::= expr(e1) T_LOGICAL_AND expr(e2). {
	A = $this->state->handle_bin_op('&&',e1,e2);
}
expr_without_variable(A) ::= expr(e1) T_LOGICAL_XOR expr(e2). {
	A = $this->state->handle_bin_op('^^',e1,e2);
}
expr_without_variable(A) ::= expr(e1) BAR expr(e2). {
	A = $this->state->handle_bin_op('|',e1,e2);
}
expr_without_variable(A) ::= expr(e1) AMPERSAND expr(e2). {
	A = $this->state->handle_bin_op('&',e1,e2);
}
expr_without_variable(A) ::= expr(e1) CARAT expr(e2). {
	A = $this->state->handle_bin_op('^',e1,e2);
}
expr_without_variable(A) ::= expr(e1) DOT expr(e2). {
	A = $this->state->handle_bin_op('.',e1,e2);
}
expr_without_variable(A) ::= expr(e1) PLUS expr(e2). {
	A = $this->state->handle_bin_op('+',e1,e2);
}
expr_without_variable(A) ::= expr(e1) MINUS expr(e2). {
	A = $this->state->handle_bin_op('-',e1,e2);
}
expr_without_variable(A) ::= expr(e1) TIMES expr(e2). {
	A = $this->state->handle_bin_op('*',e1,e2);
}
expr_without_variable(A) ::= expr(e1) DIVIDE expr(e2). {
	A = $this->state->handle_bin_op('/',e1,e2);
}
expr_without_variable(A) ::= expr(e1) PERCENT expr(e2). {
	A = $this->state->handle_bin_op('%',e1,e2);
}
expr_without_variable(A) ::= expr(e1) T_SL expr(e2). {
	A = $this->state->handle_bin_op('<<',e1,e2);
}
expr_without_variable(A) ::= expr(e1) T_SR expr(e2). {
	A = $this->state->handle_bin_op('>>',e1,e2);
}
expr_without_variable(A) ::= PLUS expr(e). {
	A = $this->state->handle_unary_op('+',e);
}
expr_without_variable(A) ::= MINUS expr(e). {
	A = $this->state->handle_unary_op('-',e);
}
expr_without_variable(A) ::= EXCLAM expr(e). {
	A = $this->state->handle_unary_op('!',e);
}
expr_without_variable(A) ::= TILDE expr(e). {
	A = $this->state->handle_unary_op('~',e);
}
expr_without_variable(A) ::= expr(e1) T_IS_IDENTICAL expr(e2). {
	A = $this->state->handle_cmp('===',e1,e2);
}
expr_without_variable(A) ::= expr(e1) T_IS_NOT_IDENTICAL expr(e2). {
	A = $this->state->handle_cmp('!==',e1,e2);
}
expr_without_variable(A) ::= expr(e1) T_IS_EQUAL expr(e2). {
	A = $this->state->handle_cmp('==',e1,e2);
}
expr_without_variable(A) ::= expr(e1) T_IS_NOT_EQUAL expr(e2). {
	A = $this->state->handle_cmp('!=',e1,e2);
}
expr_without_variable(A) ::= expr(e1) LESSTHAN expr(e2). {
	A = $this->state->handle_cmp('<',e1,e2);
}
expr_without_variable(A) ::= expr(e1) T_IS_SMALLER_OR_EQUAL expr(e2). {
	A = $this->state->handle_cmp('<=',e1,e2);
}
expr_without_variable(A) ::= expr(e1) GREATERTHAN expr(e2). {
	A = $this->state->handle_cmp('>',e1,e2);
}
expr_without_variable(A) ::= expr(e1) T_IS_GREATER_OR_EQUAL expr(e2). {
	A = $this->state->handle_cmp('>=',e1,e2);
}
expr_without_variable(A) ::= expr(e1) T_INSTANCEOF class_name_reference(name). {
	A = $this->state->handle_instanceof(e1,name);
}
expr_without_variable(A) ::= LPAREN expr(e) RPAREN. {
	A = e;
}
expr_without_variable(A) ::= expr(e1) QUESTION expr(e2) COLON expr(e3). {
	A = $this->state->handle_tri_op(e1,e2,e3);
}
expr_without_variable(A) ::= internal_functions_in_yacc(e). {
	A = e;
}
expr_without_variable(A) ::= T_INT_CAST(cast) expr(e). {
	A = $this->state->handle_cast(cast,e);
}
expr_without_variable(A) ::= T_DOUBLE_CAST(cast) expr(e). {
	A = $this->state->handle_cast(cast,e);
}
expr_without_variable(A) ::= T_STRING_CAST(cast) expr(e). {
	A = $this->state->handle_cast(cast,e);
}
expr_without_variable(A) ::= T_ARRAY_CAST(cast) expr(e). {
	A = $this->state->handle_cast(cast,e);
}
expr_without_variable(A) ::= T_OBJECT_CAST(cast) expr(e). {
	A = $this->state->handle_cast(cast,e);
}
expr_without_variable(A) ::= T_BOOL_CAST(cast) expr(e). {
	A = $this->state->handle_cast(cast,e);
}
expr_without_variable(A) ::= T_UNSET_CAST(cast) expr(e). {
	A = $this->state->handle_cast(cast,e);
}
expr_without_variable(A) ::= T_EXIT exit_expr. { A = null; }
expr_without_variable(A) ::= AT expr(e). { A = e; }
expr_without_variable(A) ::= scalar(sc). { A = sc; }
expr_without_variable(A) ::= T_ARRAY LPAREN array_pair_list(list) RPAREN. { A = list; }
expr_without_variable(A) ::= BACKQUOTE encaps_list BACKQUOTE. { A = new PC_Obj_Type(PC_Obj_Type::STRING); }
expr_without_variable(A) ::= T_PRINT expr. { A = null; }

exit_expr ::= LPAREN RPAREN.
exit_expr ::= LPAREN expr RPAREN.
exit_expr ::= .

common_scalar(A) ::= T_LNUMBER(sval). { A = new PC_Obj_Type(PC_Obj_Type::INT,sval); }
common_scalar(A) ::= T_DNUMBER(sval). { A = new PC_Obj_Type(PC_Obj_Type::FLOAT,sval); }
common_scalar(A) ::= T_CONSTANT_ENCAPSED_STRING(sval). {
	A = new PC_Obj_Type(PC_Obj_Type::STRING,substr(sval,1,-1));
}
common_scalar(A) ::= T_LINE. { A = new PC_Obj_Type(PC_Obj_Type::INT,$this->state->get_line()); }
common_scalar(A) ::= T_FILE. { A = new PC_Obj_Type(PC_Obj_Type::STRING,$this->state->get_file()); }
common_scalar(A) ::= T_CLASS_C|T_METHOD_C|T_FUNC_C. {
	// TODO value
	A = new PC_Obj_Type(PC_Obj_Type::STRING);
}

/* compile-time evaluated scalars */
static_scalar(A) ::= common_scalar(sval). { A = sval; }
static_scalar(A) ::= T_STRING(sval). { A = new PC_Obj_Type(PC_Obj_Type::STRING,sval); }
static_scalar(A) ::= T_ARRAY LPAREN static_array_pair_list(list) RPAREN. { A = list; }
static_scalar(A) ::= static_class_constant. { /* TODO */ A = null; }

static_array_pair_list(A) ::= non_empty_static_array_pair_list(list). { A = list; }
static_array_pair_list(A) ::= non_empty_static_array_pair_list(list) COMMA. { A = list; }
static_array_pair_list(A) ::= . { A = new PC_Obj_Type(PC_Obj_Type::TARRAY); }

non_empty_static_array_pair_list(A) ::= non_empty_static_array_pair_list(list) COMMA
																				static_scalar(skey) T_DOUBLE_ARROW static_scalar(sval). {
	A = list;
	A->set_array_type(skey,sval);
}
non_empty_static_array_pair_list(A) ::= non_empty_static_array_pair_list(list) COMMA
																				static_scalar(sval). {
	A = list;
	A->set_array_type(A->get_array_count(),sval);
}
non_empty_static_array_pair_list(A) ::= static_scalar(skey) T_DOUBLE_ARROW static_scalar(sval). {
	A = new PC_Obj_Type(PC_Obj_Type::TARRAY);
	A->set_array_type(skey,sval);
}
non_empty_static_array_pair_list(A) ::= static_scalar(sval). {
	A = new PC_Obj_Type(PC_Obj_Type::TARRAY);
	A->set_array_type(0,sval);
}

static_class_constant ::= T_STRING T_PAAMAYIM_NEKUDOTAYIM T_STRING.

foreach_optional_arg ::= T_DOUBLE_ARROW foreach_variable.
foreach_optional_arg ::= .

foreach_variable ::= w_variable.
foreach_variable ::= AMPERSAND w_variable.

for_statement ::= statement.
for_statement ::= COLON inner_statement_list T_ENDFOR SEMI.

foreach_statement ::= statement.
foreach_statement ::= COLON inner_statement_list T_ENDFOREACH SEMI.


declare_statement ::= statement.
declare_statement ::= COLON inner_statement_list T_ENDDECLARE SEMI.

declare_list ::= T_STRING EQUALS static_scalar.
declare_list ::= declare_list COMMA T_STRING EQUALS static_scalar.

switch_case_list ::= LCURLY case_list RCURLY.
switch_case_list ::= LCURLY SEMI case_list RCURLY.
switch_case_list ::= COLON case_list T_ENDSWITCH SEMI.
switch_case_list ::= COLON SEMI case_list T_ENDSWITCH SEMI.

case_list ::= case_list T_CASE expr case_separator.
case_list ::= case_list T_DEFAULT case_separator inner_statement_list.
case_list ::= .

case_separator ::= COLON|SEMI.

while_statement ::= statement.
while_statement ::= COLON inner_statement_list T_ENDWHILE SEMI.

elseif_list ::= elseif_list T_ELSEIF LPAREN expr RPAREN statement.
elseif_list ::= .

new_elseif_list ::= new_elseif_list T_ELSEIF LPAREN expr RPAREN COLON inner_statement_list .
new_elseif_list ::= .

else_single ::= T_ELSE statement.
else_single ::= .

new_else_single ::= T_ELSE COLON inner_statement_list.
new_else_single ::= .

parameter_list ::= non_empty_parameter_list.
parameter_list ::= .

non_empty_parameter_list ::= optional_class_type T_VARIABLE.
non_empty_parameter_list ::= optional_class_type AMPERSAND T_VARIABLE.
non_empty_parameter_list ::= optional_class_type AMPERSAND T_VARIABLE EQUALS static_scalar.
non_empty_parameter_list ::= optional_class_type T_VARIABLE EQUALS static_scalar.
non_empty_parameter_list ::= non_empty_parameter_list COMMA optional_class_type T_VARIABLE.
non_empty_parameter_list ::= non_empty_parameter_list COMMA optional_class_type AMPERSAND T_VARIABLE.
non_empty_parameter_list ::= non_empty_parameter_list COMMA optional_class_type AMPERSAND T_VARIABLE EQUALS static_scalar.
non_empty_parameter_list ::= non_empty_parameter_list COMMA optional_class_type T_VARIABLE EQUALS static_scalar.


optional_class_type ::= T_STRING|T_ARRAY.
optional_class_type ::= .

function_call_parameter_list ::= non_empty_function_call_parameter_list.
function_call_parameter_list ::= .

non_empty_function_call_parameter_list ::= expr_without_variable.
non_empty_function_call_parameter_list ::= variable.
non_empty_function_call_parameter_list ::= AMPERSAND w_variable.
non_empty_function_call_parameter_list ::= non_empty_function_call_parameter_list COMMA expr_without_variable.
non_empty_function_call_parameter_list ::= non_empty_function_call_parameter_list COMMA variable.
non_empty_function_call_parameter_list ::= non_empty_function_call_parameter_list COMMA AMPERSAND w_variable.

global_var_list ::= global_var_list COMMA global_var.
global_var_list ::= global_var.

global_var ::= T_VARIABLE.
global_var ::= DOLLAR r_variable.
global_var ::= DOLLAR LCURLY expr RCURLY.


static_var_list ::= static_var_list COMMA T_VARIABLE.
static_var_list ::= static_var_list COMMA T_VARIABLE EQUALS static_scalar.
static_var_list ::= T_VARIABLE.
static_var_list ::= T_VARIABLE EQUALS static_scalar.

class_statement_list ::= class_statement_list class_statement.
class_statement_list ::= .

class_statement ::= variable_modifiers class_variable_declaration SEMI.
class_statement ::= class_constant_declaration SEMI.
class_statement ::= method_modifiers T_FUNCTION is_reference T_STRING
										LPAREN parameter_list RPAREN method_body. {
	$this->state->end_function();
}


method_body ::= SEMI. /* abstract method */
method_body ::= LCURLY inner_statement_list RCURLY.

variable_modifiers ::= non_empty_member_modifiers.
variable_modifiers ::= T_VAR.

method_modifiers ::= non_empty_member_modifiers.
method_modifiers ::= .

non_empty_member_modifiers ::= member_modifier.
non_empty_member_modifiers ::= non_empty_member_modifiers member_modifier.

member_modifier ::= T_PUBLIC|T_PROTECTED|T_PRIVATE|T_STATIC|T_ABSTRACT|T_FINAL.

class_variable_declaration ::= class_variable_declaration COMMA T_VARIABLE.
class_variable_declaration ::= class_variable_declaration COMMA T_VARIABLE EQUALS static_scalar.
class_variable_declaration ::= T_VARIABLE.
class_variable_declaration ::= T_VARIABLE EQUALS static_scalar.

class_constant_declaration ::= class_constant_declaration COMMA T_STRING EQUALS static_scalar.
class_constant_declaration ::= T_CONST T_STRING EQUALS static_scalar.

echo_expr_list ::= echo_expr_list COMMA expr.
echo_expr_list ::= expr.

unset_variables ::= unset_variable.
unset_variables ::= unset_variables COMMA unset_variable.

unset_variable ::= variable.

use_filename ::= T_CONSTANT_ENCAPSED_STRING.
use_filename ::= LCURLY T_CONSTANT_ENCAPSED_STRING RCURLY.

r_variable(A) ::= variable(v). { A = v; }

w_variable(A) ::= variable(v). { A = v; }

rw_variable(A) ::= variable(v). { A = v; }

variable(A) ::= base_variable_with_function_calls T_OBJECT_OPERATOR object_property
								method_or_not variable_properties. {
	// TODO
	A = null;
}
variable(A) ::= base_variable_with_function_calls(v). {
	A = v;
}

variable_properties ::= variable_properties variable_property.
variable_properties ::= .

variable_property ::= T_OBJECT_OPERATOR object_property method_or_not.

method_or_not ::= LPAREN function_call_parameter_list RPAREN.
method_or_not ::= .

variable_without_objects ::= reference_variable.
variable_without_objects ::= simple_indirect_reference reference_variable.

static_member ::= fully_qualified_class_name T_PAAMAYIM_NEKUDOTAYIM variable_without_objects.

base_variable_with_function_calls(A) ::= base_variable(v). { A = v; }
base_variable_with_function_calls(A) ::= function_call(call). {
	A = $this->state->get_func_retval(call);
}

base_variable(A) ::= reference_variable(v). { A = v; }
base_variable(A) ::= simple_indirect_reference reference_variable. { /* TODO */ A = null; }
base_variable(A) ::= static_member. { /* TODO */ A = null; }
	
reference_variable(A) ::= reference_variable(v) LBRACKET dim_offset(off) RBRACKET. {
	A = $this->state->handle_array_access(v,off);
}
reference_variable(A) ::= reference_variable LCURLY expr RCURLY. {
	// TODO
	A = null;
}
reference_variable(A) ::= compound_variable(v). {
	A = v;
}

compound_variable(A) ::= T_VARIABLE(name). {
	A = $this->state->get_var(substr(name,1));
}
compound_variable(A) ::= DOLLAR LCURLY expr(e) RCURLY. {
	if(e->get_value() !== null)
		A = $this->state->get_var(e->get_value_as_str());
	else
		A = null;
}

dim_offset(A) ::= expr(e). { A = e; }
dim_offset(A) ::= . { A = null; }

object_property ::= object_dim_list.
object_property ::= variable_without_objects.

object_dim_list ::= object_dim_list LBRACKET dim_offset RBRACKET.
object_dim_list ::= object_dim_list LCURLY expr RCURLY.
object_dim_list ::= variable_name .

variable_name ::= T_STRING.
variable_name ::= LCURLY expr RCURLY.

simple_indirect_reference ::= DOLLAR.
simple_indirect_reference ::= simple_indirect_reference DOLLAR.

assignment_list ::= assignment_list COMMA assignment_list_element.
assignment_list ::= assignment_list_element.

assignment_list_element ::= variable.
assignment_list_element ::= T_LIST LPAREN assignment_list RPAREN.
assignment_list_element ::= .

array_pair_list ::= non_empty_array_pair_list possible_comma.
array_pair_list ::= .

non_empty_array_pair_list ::= non_empty_array_pair_list COMMA expr T_DOUBLE_ARROW expr.
non_empty_array_pair_list ::= non_empty_array_pair_list COMMA expr.
non_empty_array_pair_list ::= expr T_DOUBLE_ARROW expr.
non_empty_array_pair_list ::= expr.
non_empty_array_pair_list ::= non_empty_array_pair_list COMMA expr T_DOUBLE_ARROW AMPERSAND w_variable.
non_empty_array_pair_list ::= non_empty_array_pair_list COMMA AMPERSAND w_variable.
non_empty_array_pair_list ::= expr T_DOUBLE_ARROW AMPERSAND w_variable.
non_empty_array_pair_list ::= AMPERSAND w_variable.

encaps_list ::= encaps_list encaps_var.
encaps_list ::= encaps_list T_STRING.
encaps_list ::= encaps_list T_NUM_STRING.
encaps_list ::= encaps_list T_ENCAPSED_AND_WHITESPACE.
encaps_list ::= encaps_list T_CHARACTER.
encaps_list ::= encaps_list T_BAD_CHARACTER.
encaps_list ::= encaps_list LBRACKET.
encaps_list ::= encaps_list RBRACKET.
encaps_list ::= encaps_list LCURLY.
encaps_list ::= encaps_list RCURLY.
encaps_list ::= encaps_list T_OBJECT_OPERATOR.
encaps_list ::= .



encaps_var ::= T_VARIABLE.
encaps_var ::= T_VARIABLE LBRACKET encaps_var_offset RBRACKET.
encaps_var ::= T_VARIABLE T_OBJECT_OPERATOR T_STRING.
encaps_var ::= T_DOLLAR_OPEN_CURLY_BRACES expr RCURLY.
encaps_var ::= T_DOLLAR_OPEN_CURLY_BRACES T_STRING_VARNAME LBRACKET expr RBRACKET RCURLY.
encaps_var ::= T_CURLY_OPEN variable RCURLY.

encaps_var_offset ::= T_STRING|T_NUM_STRING|T_VARIABLE.

internal_functions_in_yacc ::= T_ISSET LPAREN isset_variables RPAREN.
internal_functions_in_yacc ::= T_EMPTY LPAREN variable RPAREN.
internal_functions_in_yacc ::= T_INCLUDE expr.
internal_functions_in_yacc ::= T_INCLUDE_ONCE expr.
internal_functions_in_yacc ::= T_EVAL LPAREN expr RPAREN.
internal_functions_in_yacc ::= T_REQUIRE expr.
internal_functions_in_yacc ::= T_REQUIRE_ONCE expr.

isset_variables ::= variable.
isset_variables ::= isset_variables COMMA variable.

class_constant ::= fully_qualified_class_name T_PAAMAYIM_NEKUDOTAYIM T_STRING.

fully_qualified_class_name ::= T_STRING.

function_call ::= T_STRING LPAREN function_call_parameter_list RPAREN.
function_call ::= fully_qualified_class_name T_PAAMAYIM_NEKUDOTAYIM T_STRING LPAREN function_call_parameter_list RPAREN.
function_call ::= fully_qualified_class_name T_PAAMAYIM_NEKUDOTAYIM variable_without_objects LPAREN function_call_parameter_list RPAREN.
function_call ::= variable_without_objects LPAREN function_call_parameter_list RPAREN.

scalar(A) ::= T_STRING(str). { A = new PC_Obj_Type(PC_Obj_Type::STRING,str); }
scalar(A) ::= T_STRING_VARNAME. { A = new PC_Obj_Type(PC_Obj_Type::STRING); }
scalar(A) ::= class_constant. { /* TODO */ A = null; }
scalar(A) ::= common_scalar(sc). { A = sc; }
scalar(A) ::= DOUBLEQUOTE encaps_list DOUBLEQUOTE. { A = new PC_Obj_Type(PC_Obj_Type::STRING); }
scalar(A) ::= SINGLEQUOTE encaps_list SINGLEQUOTE. { A = new PC_Obj_Type(PC_Obj_Type::STRING); }
scalar(A) ::= T_START_HEREDOC encaps_list T_END_HEREDOC. { A = new PC_Obj_Type(PC_Obj_Type::STRING); }

class_name_reference ::= T_STRING.
class_name_reference ::= dynamic_class_name_reference.

dynamic_class_name_reference ::= base_variable T_OBJECT_OPERATOR object_property dynamic_class_name_variable_properties.
dynamic_class_name_reference ::= base_variable.

dynamic_class_name_variable_properties ::= dynamic_class_name_variable_properties dynamic_class_name_variable_property.
dynamic_class_name_variable_properties ::= .

dynamic_class_name_variable_property ::= T_OBJECT_OPERATOR object_property.

ctor_arguments ::= LPAREN function_call_parameter_list RPAREN.
ctor_arguments ::= .

possible_comma ::= COMMA.
possible_comma ::= .

for_expr ::= non_empty_for_expr.
for_expr ::= .

non_empty_for_expr ::= non_empty_for_expr COMMA expr.
non_empty_for_expr ::= expr.

is_reference ::= AMPERSAND.
is_reference ::= .