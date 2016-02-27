%name PC_Type_
%declare_class {class PC_Engine_TypeParser}

%syntax_error {
    foreach ($this->yy_get_expected_tokens($yymajor) as $token) {
        $expect[] = self::$yyTokenName[$token];
    }
		throw new PC_Engine_Exception(
			$this->state->get_file(),$this->state->get_line(),$this->tokenName($yymajor),$TOKEN,$expect
		);
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

%left T_INCLUDE T_INCLUDE_ONCE T_EVAL T_REQUIRE T_REQUIRE_ONCE .
%left COMMA .
%left T_LOGICAL_OR .
%left T_LOGICAL_XOR .
%left T_LOGICAL_AND .
%right T_PRINT .
%right T_YIELD .
%right T_DOUBLE_ARROW .
%right T_YIELD_FROM .
%left EQUALS T_PLUS_EQUAL T_MINUS_EQUAL T_MUL_EQUAL T_DIV_EQUAL T_CONCAT_EQUAL T_MOD_EQUAL T_AND_EQUAL T_OR_EQUAL T_XOR_EQUAL T_SL_EQUAL T_SR_EQUAL T_POW_EQUAL .
%left QUESTION COLON .
%right T_COALESCE .
%left T_BOOLEAN_OR .
%left T_BOOLEAN_AND .
%left BAR .
%left CARAT .
%left AMPERSAND .
%nonassoc T_IS_EQUAL T_IS_NOT_EQUAL T_IS_IDENTICAL T_IS_NOT_IDENTICAL T_SPACESHIP .
%nonassoc LESSTHAN T_IS_SMALLER_OR_EQUAL GREATERTHAN T_IS_GREATER_OR_EQUAL .
%left T_SL T_SR .
%left PLUS MINUS DOT .
%left TIMES DIVIDE PERCENT .
%right EXCLAM .
%nonassoc T_INSTANCEOF .
%right TILDE T_INC T_DEC T_INT_CAST T_DOUBLE_CAST T_STRING_CAST T_ARRAY_CAST T_OBJECT_CAST T_BOOL_CAST T_UNSET_CAST AT .
%right T_POW .
%right LBRACKET .
%nonassoc T_NEW T_CLONE .
%left T_NOELSE .
%left T_ELSEIF .
%left T_ELSE .
%left T_ENDIF .
%right T_STATIC T_ABSTRACT T_FINAL T_PRIVATE T_PROTECTED T_PUBLIC .
%nonassoc SINGLEQUOTE .

start ::= top_statement_list .

reserved_non_modifiers(A) ::= T_INCLUDE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_INCLUDE_ONCE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_EVAL(t) . { A = t; }
reserved_non_modifiers(A) ::= T_REQUIRE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_REQUIRE_ONCE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_LOGICAL_OR(t) . { A = t; }
reserved_non_modifiers(A) ::= T_LOGICAL_XOR(t) . { A = t; }
reserved_non_modifiers(A) ::= T_LOGICAL_AND(t) . { A = t; }
reserved_non_modifiers(A) ::= T_INSTANCEOF(t) . { A = t; }
reserved_non_modifiers(A) ::= T_NEW(t) . { A = t; }
reserved_non_modifiers(A) ::= T_CLONE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_EXIT(t) . { A = t; }
reserved_non_modifiers(A) ::= T_IF(t) . { A = t; }
reserved_non_modifiers(A) ::= T_ELSEIF(t) . { A = t; }
reserved_non_modifiers(A) ::= T_ELSE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_ENDIF(t) . { A = t; }
reserved_non_modifiers(A) ::= T_ECHO(t) . { A = t; }
reserved_non_modifiers(A) ::= T_DO(t) . { A = t; }
reserved_non_modifiers(A) ::= T_WHILE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_ENDWHILE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_FOR(t) . { A = t; }
reserved_non_modifiers(A) ::= T_ENDFOR(t) . { A = t; }
reserved_non_modifiers(A) ::= T_FOREACH(t) . { A = t; }
reserved_non_modifiers(A) ::= T_ENDFOREACH(t) . { A = t; }
reserved_non_modifiers(A) ::= T_DECLARE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_ENDDECLARE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_AS(t) . { A = t; }
reserved_non_modifiers(A) ::= T_TRY(t) . { A = t; }
reserved_non_modifiers(A) ::= T_CATCH(t) . { A = t; }
reserved_non_modifiers(A) ::= T_FINALLY(t) . { A = t; }
reserved_non_modifiers(A) ::= T_THROW(t) . { A = t; }
reserved_non_modifiers(A) ::= T_USE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_INSTEADOF(t) . { A = t; }
reserved_non_modifiers(A) ::= T_GLOBAL(t) . { A = t; }
reserved_non_modifiers(A) ::= T_VAR(t) . { A = t; }
reserved_non_modifiers(A) ::= T_UNSET(t) . { A = t; }
reserved_non_modifiers(A) ::= T_ISSET(t) . { A = t; }
reserved_non_modifiers(A) ::= T_EMPTY(t) . { A = t; }
reserved_non_modifiers(A) ::= T_CONTINUE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_GOTO(t) . { A = t; }
reserved_non_modifiers(A) ::= T_FUNCTION(t) . { A = t; }
reserved_non_modifiers(A) ::= T_CONST(t) . { A = t; }
reserved_non_modifiers(A) ::= T_RETURN(t) . { A = t; }
reserved_non_modifiers(A) ::= T_PRINT(t) . { A = t; }
reserved_non_modifiers(A) ::= T_YIELD(t) . { A = t; }
reserved_non_modifiers(A) ::= T_LIST(t) . { A = t; }
reserved_non_modifiers(A) ::= T_SWITCH(t) . { A = t; }
reserved_non_modifiers(A) ::= T_ENDSWITCH(t) . { A = t; }
reserved_non_modifiers(A) ::= T_CASE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_DEFAULT(t) . { A = t; }
reserved_non_modifiers(A) ::= T_BREAK(t) . { A = t; }
reserved_non_modifiers(A) ::= T_ARRAY(t) . { A = t; }
reserved_non_modifiers(A) ::= T_CALLABLE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_EXTENDS(t) . { A = t; }
reserved_non_modifiers(A) ::= T_IMPLEMENTS(t) . { A = t; }
reserved_non_modifiers(A) ::= T_NAMESPACE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_TRAIT(t) . { A = t; }
reserved_non_modifiers(A) ::= T_INTERFACE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_CLASS(t) . { A = t; }
reserved_non_modifiers(A) ::= T_CLASS_C(t) . { A = t; }
reserved_non_modifiers(A) ::= T_TRAIT_C(t) . { A = t; }
reserved_non_modifiers(A) ::= T_FUNC_C(t) . { A = t; }
reserved_non_modifiers(A) ::= T_METHOD_C(t) . { A = t; }
reserved_non_modifiers(A) ::= T_LINE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_FILE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_DIR(t) . { A = t; }
reserved_non_modifiers(A) ::= T_NS_C(t) . { A = t; }
reserved_non_modifiers(A) ::= T_HALT_COMPILER(t) . { A = t; }

semi_reserved(A) ::= reserved_non_modifiers(t) . { A = t; }
semi_reserved(A) ::= T_STATIC(t) . { A = t; }
semi_reserved(A) ::= T_ABSTRACT(t) . { A = t; }
semi_reserved(A) ::= T_FINAL(t) . { A = t; }
semi_reserved(A) ::= T_PRIVATE(t) . { A = t; }
semi_reserved(A) ::= T_PROTECTED(t) . { A = t; }
semi_reserved(A) ::= T_PUBLIC(t) . { A = t; }

identifier(A) ::= T_STRING(n) . { A = n; }
identifier(A) ::= semi_reserved(t) . { A = t; }

top_statement_list ::= top_statement_list top_statement .
top_statement_list ::= /* empty */ .

namespace_name(A) ::= T_STRING(name) . { A = name; }
namespace_name(A) ::= namespace_name T_NS_SEPARATOR T_STRING(name) . {
	// TODO
	A = name;
}

name(A) ::= namespace_name(name) . { A = name; }
name(A) ::= T_NAMESPACE T_NS_SEPARATOR namespace_name(name) . { A = name; }
name(A) ::= T_NS_SEPARATOR namespace_name(name) . { A = name; }

top_statement ::= statement .
top_statement ::= function_declaration_statement .
top_statement ::= class_declaration_statement .
top_statement ::= trait_declaration_statement .
top_statement ::= interface_declaration_statement .
top_statement ::= T_HALT_COMPILER LPAREN RPAREN SEMI .
top_statement ::= T_NAMESPACE namespace_name SEMI .
top_statement ::= T_NAMESPACE namespace_name LCURLY top_statement_list RCURLY .
top_statement ::= T_NAMESPACE LCURLY top_statement_list RCURLY .
top_statement ::= T_USE mixed_group_use_declaration SEMI .
top_statement ::= T_USE use_type group_use_declaration SEMI .
top_statement ::= T_USE use_declarations SEMI .
top_statement ::= T_USE use_type use_declarations SEMI .
top_statement ::= T_CONST const_list SEMI .

use_type ::= T_FUNCTION .
use_type ::= T_CONST .

group_use_declaration ::= namespace_name T_NS_SEPARATOR LCURLY unprefixed_use_declarations RCURLY .
group_use_declaration ::= T_NS_SEPARATOR namespace_name T_NS_SEPARATOR LCURLY unprefixed_use_declarations RCURLY .

mixed_group_use_declaration ::= namespace_name T_NS_SEPARATOR LCURLY inline_use_declarations RCURLY .
mixed_group_use_declaration ::= T_NS_SEPARATOR namespace_name T_NS_SEPARATOR LCURLY inline_use_declarations RCURLY .

inline_use_declarations ::= inline_use_declarations COMMA inline_use_declaration .
inline_use_declarations ::= inline_use_declaration .

unprefixed_use_declarations ::= unprefixed_use_declarations COMMA unprefixed_use_declaration .
unprefixed_use_declarations ::= unprefixed_use_declaration .

use_declarations ::= use_declarations COMMA use_declaration .
use_declarations ::= use_declaration .

inline_use_declaration ::= unprefixed_use_declaration .
inline_use_declaration ::= use_type unprefixed_use_declaration .

unprefixed_use_declaration ::= namespace_name .
unprefixed_use_declaration ::= namespace_name T_AS T_STRING .

use_declaration ::= unprefixed_use_declaration .
use_declaration ::= T_NS_SEPARATOR unprefixed_use_declaration .

const_list ::= const_list COMMA const_decl .
const_list ::= const_decl .

inner_statement_list ::= inner_statement_list inner_statement .
inner_statement_list ::= /* empty */ .

inner_statement ::= statement .
inner_statement ::= function_declaration_statement .
inner_statement ::= class_declaration_statement .
inner_statement ::= trait_declaration_statement .
inner_statement ::= interface_declaration_statement .
inner_statement ::= T_HALT_COMPILER LPAREN RPAREN SEMI .

statement ::= LCURLY inner_statement_list RCURLY .
statement ::= if_stmt .
statement ::= alt_if_stmt .
statement ::= T_WHILE LPAREN expr RPAREN while_statement .
statement ::= T_DO statement T_WHILE LPAREN expr RPAREN SEMI .
statement ::= T_FOR LPAREN for_exprs SEMI for_exprs SEMI for_exprs RPAREN for_statement .
statement ::= T_SWITCH LPAREN expr RPAREN switch_case_list .
statement ::= T_BREAK optional_expr SEMI .
statement ::= T_CONTINUE optional_expr SEMI .
statement ::= T_RETURN optional_expr SEMI .
statement ::= T_GLOBAL global_var_list SEMI .
statement ::= T_STATIC static_var_list SEMI .
statement ::= T_ECHO echo_expr_list SEMI .
statement ::= T_INLINE_HTML .
statement ::= expr SEMI .
statement ::= T_UNSET LPAREN unset_variables RPAREN SEMI .
statement ::= T_FOREACH LPAREN expr T_AS foreach_variable RPAREN foreach_statement .
statement ::= T_FOREACH LPAREN expr T_AS foreach_variable T_DOUBLE_ARROW foreach_variable RPAREN foreach_statement .
statement ::= T_DECLARE LPAREN const_list RPAREN declare_statement .
statement ::= SEMI /* empty statement */ .
statement ::= T_TRY LCURLY inner_statement_list RCURLY catch_list finally_statement .
statement ::= T_THROW expr SEMI .
statement ::= T_GOTO T_STRING SEMI .
statement ::= T_STRING COLON .

catch_list ::= /* empty */ .
catch_list ::= catch_list T_CATCH LPAREN name T_VARIABLE RPAREN LCURLY inner_statement_list RCURLY .

finally_statement ::= /* empty */ .
finally_statement ::= T_FINALLY LCURLY inner_statement_list RCURLY .

unset_variables ::= unset_variable .
unset_variables ::= unset_variables COMMA unset_variable .

unset_variable ::= variable .

function_declaration_statement ::= function returns_ref T_STRING(name)
																	 LPAREN parameter_list(params) RPAREN
																	 return_type(ret) backup_doc_comment
																	 LCURLY inner_statement_list RCURLY . {
	$this->state->declare_function(name,params,ret);
}

is_reference ::= /* empty */ .
is_reference ::= AMPERSAND .

is_variadic ::= /* empty */ .
is_variadic ::= T_ELLIPSIS .

class_declaration_statement ::= class_modifiers(modifier) T_STRING(name) extends_from(extends)
																implements_list(implements)
																backup_doc_comment LCURLY class_statement_list(stmts) RCURLY . {
	$this->state->declare_class(
		name,modifier,extends,implements,stmts
	);
}

class_modifiers(A) ::= T_CLASS . { A = array(); }
class_modifiers(A) ::= T_ABSTRACT T_CLASS . { A = array('abstract' => true); }
class_modifiers(A) ::= T_FINAL T_CLASS . { A = array('final' => true); }

trait_declaration_statement ::= T_TRAIT T_STRING backup_doc_comment LCURLY class_statement_list RCURLY .

interface_declaration_statement ::= T_INTERFACE T_STRING(name) interface_extends_list(extends)
																		backup_doc_comment LCURLY class_statement_list(stmts) RCURLY . {
	$this->state->declare_interface(name,extends,stmts);
}

extends_from(A) ::= /* empty */ . { A = null; }
extends_from(A) ::= T_EXTENDS name(n) . { A = n; }

interface_extends_list(A) ::= /* empty */ . { A = array(); }
interface_extends_list(A) ::= T_EXTENDS name_list(list) . { A = list; }

implements_list(A) ::= /* empty */ . { A = array(); }
implements_list(A) ::= T_IMPLEMENTS name_list(list) . { A = list; }

foreach_variable ::= variable .
foreach_variable ::= AMPERSAND variable .
foreach_variable ::= T_LIST LPAREN assignment_list RPAREN .

for_statement ::= statement .
for_statement ::= COLON inner_statement_list T_ENDFOR SEMI .

foreach_statement ::= statement .
foreach_statement ::= COLON inner_statement_list T_ENDFOREACH SEMI .

declare_statement ::= statement .
declare_statement ::= COLON inner_statement_list T_ENDDECLARE SEMI .

switch_case_list ::= LCURLY case_list RCURLY .
switch_case_list ::= LCURLY SEMI case_list RCURLY .
switch_case_list ::= COLON case_list T_ENDSWITCH SEMI .
switch_case_list ::= COLON SEMI case_list T_ENDSWITCH SEMI .

case_list ::= /* empty */ .
case_list ::= case_list T_CASE expr case_separator inner_statement_list .
case_list ::= case_list T_DEFAULT case_separator inner_statement_list .

case_separator ::= COLON .
case_separator ::= SEMI .

while_statement ::= statement .
while_statement ::= COLON inner_statement_list T_ENDWHILE SEMI .

if_stmt_without_else ::= T_IF LPAREN expr RPAREN statement .
if_stmt_without_else ::= if_stmt_without_else T_ELSEIF LPAREN expr RPAREN statement .

if_stmt ::= if_stmt_without_else .
if_stmt ::= if_stmt_without_else T_ELSE statement .

alt_if_stmt_without_else ::= T_IF LPAREN expr RPAREN COLON inner_statement_list .
alt_if_stmt_without_else ::= alt_if_stmt_without_else T_ELSEIF LPAREN expr RPAREN COLON inner_statement_list .

alt_if_stmt ::= alt_if_stmt_without_else T_ENDIF SEMI .
alt_if_stmt ::= alt_if_stmt_without_else T_ELSE COLON inner_statement_list T_ENDIF SEMI .

parameter_list(A) ::= non_empty_parameter_list(list) . { A = list; }
parameter_list(A) ::= /* empty */ . { A = array(); }

non_empty_parameter_list(A) ::= parameter(p) . {
	A = array();
	A[] = p;
}
non_empty_parameter_list(A) ::= non_empty_parameter_list(list) COMMA parameter(p) . {
	A = list;
	A[] = p;
}

parameter(A) ::= optional_type(vtype) is_reference is_variadic T_VARIABLE(vname) . {
	A = $this->state->create_parameter(substr(vname,1),vtype,null,false);
}
parameter(A) ::= optional_type(vtype) is_reference is_variadic T_VARIABLE(vname) EQUALS expr(vval) . {
	A = $this->state->create_parameter(substr(vname,1),vtype,vval,true);
}

optional_type(A) ::= /* empty */ . { A = new PC_Obj_MultiType(); }
optional_type(A) ::= type(t) . { A = t; }

type(A) ::= T_ARRAY . { A = PC_Obj_MultiType::create_array(); }
type(A) ::= T_CALLABLE . { A = PC_Obj_MultiType::create_callable(); }
type(A) ::= name(vtype) . { A = $this->state->get_type_by_name(vtype); }

return_type(A) ::= /* empty */ . { A = null; }
return_type(A) ::= COLON type(t) . { A = t; }

argument_list(A) ::= LPAREN RPAREN . { A = array(); }
argument_list(A) ::= LPAREN non_empty_argument_list(list) RPAREN . { A = list; }

non_empty_argument_list(A) ::= argument(arg) . { A = array(arg); }
non_empty_argument_list(A) ::= non_empty_argument_list(list) COMMA argument(arg) . {
	A = list;
	A[] = arg;
}

argument(A) ::= expr(e) . { A = e; }
argument ::= T_ELLIPSIS expr .

global_var_list ::= global_var_list COMMA global_var .
global_var_list ::= global_var .

global_var ::= simple_variable .

static_var_list ::= static_var_list COMMA static_var .
static_var_list ::= static_var .

static_var ::= T_VARIABLE .
static_var ::= T_VARIABLE EQUALS expr .

class_statement_list(A) ::= class_statement_list(list) class_statement(stmt) . {
	A = list;
	foreach(stmt as $st)
		A[] = $st;
}
class_statement_list(A) ::= /* empty */ . { A = array(); }

class_statement(A) ::= variable_modifiers(mmodifiers) property_list(vars) SEMI . {
	A = $this->state->create_fields(vars,mmodifiers);
}
class_statement(A) ::= method_modifiers T_CONST class_const_list(consts) SEMI . {
	A = $this->state->create_consts(consts);
}
class_statement(A) ::= T_USE name_list trait_adaptations . {
	// TODO
	A = array();
}
class_statement(A) ::= method_modifiers(mmodifiers) function returns_ref identifier(mname)
										LPAREN parameter_list(mparams) RPAREN
										return_type(mret) backup_doc_comment method_body . {
	A = array();
	A[] = $this->state->create_method(mname,mmodifiers,mparams,mret);
}

name_list(A) ::= name(n) . {
	A = array(n);
}
name_list(A) ::= name_list(list) COMMA name(n) . {
	A = list;
	A[] = n;
}

trait_adaptations ::= SEMI .
trait_adaptations ::= LCURLY RCURLY .
trait_adaptations ::= LCURLY trait_adaptation_list RCURLY .

trait_adaptation_list ::= trait_adaptation .
trait_adaptation_list ::= trait_adaptation_list trait_adaptation .

trait_adaptation ::= trait_precedence SEMI .
trait_adaptation ::= trait_alias SEMI .

trait_precedence ::= absolute_trait_method_reference T_INSTEADOF name_list .

trait_alias ::= trait_method_reference T_AS T_STRING .
trait_alias ::= trait_method_reference T_AS reserved_non_modifiers .
trait_alias ::= trait_method_reference T_AS member_modifier identifier .
trait_alias ::= trait_method_reference T_AS member_modifier .

trait_method_reference ::= identifier .
trait_method_reference ::= absolute_trait_method_reference .

absolute_trait_method_reference ::= name T_PAAMAYIM_NEKUDOTAYIM identifier .

method_body ::= SEMI /* abstract method */ .
method_body ::= LCURLY inner_statement_list RCURLY .

variable_modifiers(A) ::= non_empty_member_modifiers(mods) . { A = mods; }
variable_modifiers(A) ::= T_VAR . { A = array('public'); }

method_modifiers(A) ::= /* empty */ . { A = array(); }
method_modifiers(A) ::= non_empty_member_modifiers(mods) . { A = mods; }

non_empty_member_modifiers(A) ::= member_modifier(mod) . { A = array(mod); }
non_empty_member_modifiers(A) ::= non_empty_member_modifiers(mods) member_modifier(mod) . {
	A = mods;
	A[] = mod;
}

member_modifier(A) ::= T_PUBLIC|T_PROTECTED|T_PRIVATE|T_STATIC|T_ABSTRACT|T_FINAL(mod) . {
	A = mod;
}

property_list(A) ::= property_list(list) COMMA property(p) . { A = list; A[] = p; }
property_list(A) ::= property(p) . { A = array(p); }

property(A) ::= T_VARIABLE(varname) backup_doc_comment . { A = array('name' => substr(varname,1)); }
property(A) ::= T_VARIABLE(varname) EQUALS expr(varval) backup_doc_comment . {
	A = array('name' => substr(varname,1),'val' => varval);
}

class_const_list(A) ::= class_const_list(list) COMMA class_const_decl(d) . { A = list; A[] = d; }
class_const_list(A) ::= class_const_decl(d) . { A = array(d); }

class_const_decl(A) ::= identifier(cname) EQUALS expr(cvalue) backup_doc_comment . {
	A = array('name' => cname,'val' => cvalue);
}

const_decl ::= T_STRING EQUALS expr backup_doc_comment .

echo_expr_list ::= echo_expr_list COMMA echo_expr .
echo_expr_list ::= echo_expr .

echo_expr ::= expr .

for_exprs ::= /* empty */ .
for_exprs ::= non_empty_for_exprs .

non_empty_for_exprs ::= non_empty_for_exprs COMMA expr .
non_empty_for_exprs ::= expr .

anonymous_class ::= T_CLASS ctor_arguments extends_from(extends) implements_list(implements)
										backup_doc_comment LCURLY class_statement_list(stmts) RCURLY . {
	$this->state->declare_class(
		'',array(),extends,implements,stmts
	);
}

new_expr ::= T_NEW class_name_reference ctor_arguments .
new_expr ::= T_NEW anonymous_class .

expr_without_variable ::= T_LIST LPAREN assignment_list RPAREN EQUALS expr .
expr_without_variable ::= variable EQUALS expr .
expr_without_variable ::= variable EQUALS AMPERSAND variable .
expr_without_variable ::= T_CLONE expr .
expr_without_variable ::= variable T_PLUS_EQUAL expr .
expr_without_variable ::= variable T_MINUS_EQUAL expr .
expr_without_variable ::= variable T_MUL_EQUAL expr .
expr_without_variable ::= variable T_POW_EQUAL expr .
expr_without_variable ::= variable T_DIV_EQUAL expr .
expr_without_variable ::= variable T_CONCAT_EQUAL expr .
expr_without_variable ::= variable T_MOD_EQUAL expr .
expr_without_variable ::= variable T_AND_EQUAL expr .
expr_without_variable ::= variable T_OR_EQUAL expr .
expr_without_variable ::= variable T_XOR_EQUAL expr .
expr_without_variable ::= variable T_SL_EQUAL expr .
expr_without_variable ::= variable T_SR_EQUAL expr .
expr_without_variable ::= variable T_INC .
expr_without_variable ::= T_INC variable .
expr_without_variable ::= variable T_DEC .
expr_without_variable ::= T_DEC variable .
expr_without_variable ::= expr T_BOOLEAN_OR expr .
expr_without_variable ::= expr T_BOOLEAN_AND expr .
expr_without_variable ::= expr T_LOGICAL_OR expr .
expr_without_variable ::= expr T_LOGICAL_AND expr .
expr_without_variable ::= expr T_LOGICAL_XOR expr .
expr_without_variable ::= expr BAR expr .
expr_without_variable ::= expr AMPERSAND expr .
expr_without_variable ::= expr CARAT expr .
expr_without_variable ::= expr DOT expr .
expr_without_variable ::= expr PLUS expr .
expr_without_variable ::= expr MINUS expr .
expr_without_variable ::= expr TIMES expr .
expr_without_variable ::= expr T_POW expr .
expr_without_variable ::= expr DIVIDE expr .
expr_without_variable ::= expr PERCENT expr .
expr_without_variable ::= expr T_SL expr .
expr_without_variable ::= expr T_SR expr .
expr_without_variable ::= PLUS expr .
expr_without_variable ::= PLUS expr T_INC . [T_INC]
expr_without_variable ::= MINUS expr .
expr_without_variable ::= MINUS expr T_INC . [T_INC]
expr_without_variable ::= EXCLAM expr .
expr_without_variable ::= TILDE expr .
expr_without_variable ::= expr T_IS_IDENTICAL expr .
expr_without_variable ::= expr T_IS_NOT_IDENTICAL expr .
expr_without_variable ::= expr T_IS_EQUAL expr .
expr_without_variable ::= expr T_IS_NOT_EQUAL expr .
expr_without_variable ::= expr LESSTHAN expr .
expr_without_variable ::= expr T_IS_SMALLER_OR_EQUAL expr .
expr_without_variable ::= expr GREATERTHAN expr .
expr_without_variable ::= expr T_IS_GREATER_OR_EQUAL expr .
expr_without_variable ::= expr T_SPACESHIP expr .
expr_without_variable ::= expr T_INSTANCEOF class_name_reference .
expr_without_variable ::= LPAREN expr RPAREN .
expr_without_variable ::= new_expr .
expr_without_variable ::= expr QUESTION expr COLON expr .
expr_without_variable ::= expr QUESTION COLON expr .
expr_without_variable ::= expr T_COALESCE expr .
expr_without_variable ::= internal_functions_in_yacc .
expr_without_variable ::= T_INT_CAST expr .
expr_without_variable ::= T_DOUBLE_CAST expr .
expr_without_variable ::= T_STRING_CAST expr .
expr_without_variable ::= T_ARRAY_CAST expr .
expr_without_variable ::= T_OBJECT_CAST expr .
expr_without_variable ::= T_BOOL_CAST expr .
expr_without_variable ::= T_UNSET_CAST expr .
expr_without_variable ::= T_EXIT exit_expr .
expr_without_variable ::= AT expr .
expr_without_variable(A) ::= scalar(s) . { A = s; }
expr_without_variable ::= BACKQUOTE backticks_expr BACKQUOTE .
expr_without_variable ::= T_PRINT expr .
expr_without_variable ::= T_YIELD .
expr_without_variable ::= T_YIELD expr .
expr_without_variable ::= T_YIELD expr T_DOUBLE_ARROW expr .
expr_without_variable ::= T_YIELD_FROM expr .
expr_without_variable ::= function returns_ref LPAREN parameter_list(mparams) RPAREN
													lexical_vars return_type(mret) backup_doc_comment
													LCURLY inner_statement_list RCURLY . {
	$this->state->declare_function('',mparams,mret);
}
expr_without_variable ::= T_STATIC function returns_ref LPAREN parameter_list(mparams) RPAREN
													lexical_vars return_type(mret) backup_doc_comment
													LCURLY inner_statement_list RCURLY . {
	$this->state->declare_function('',mparams,mret);
}

function ::= T_FUNCTION .

backup_doc_comment ::= /* empty */ .

returns_ref ::= /* empty */ .
returns_ref ::= AMPERSAND .

lexical_vars ::= /* empty */ .
lexical_vars ::= T_USE LPAREN lexical_var_list RPAREN .

lexical_var_list ::= lexical_var_list COMMA lexical_var .
lexical_var_list ::= lexical_var .

lexical_var ::= T_VARIABLE .
lexical_var ::= AMPERSAND T_VARIABLE .

function_call ::= name(n) argument_list(args) . {
	if(strcasecmp(n,'define') == 0)
		$this->state->handle_define(args);
}
function_call ::= class_name T_PAAMAYIM_NEKUDOTAYIM member_name argument_list .
function_call ::= variable_class_name T_PAAMAYIM_NEKUDOTAYIM member_name argument_list .
function_call ::= callable_expr argument_list .

class_name ::= T_STATIC .
class_name ::= name .

class_name_reference ::= class_name .
class_name_reference ::= new_variable .

exit_expr ::= /* empty */ .
exit_expr ::= LPAREN optional_expr RPAREN .

backticks_expr ::= /* empty */ .
backticks_expr ::= T_ENCAPSED_AND_WHITESPACE .
backticks_expr ::= encaps_list .

ctor_arguments ::= /* empty */ .
ctor_arguments ::= argument_list .

dereferencable_scalar(A) ::= T_ARRAY LPAREN array_pair_list(list) RPAREN . { A = list; }
dereferencable_scalar(A) ::= LBRACKET array_pair_list(list) RBRACKET . { A = list; }
dereferencable_scalar(A) ::= T_CONSTANT_ENCAPSED_STRING(sval) . {
	A = PC_Obj_MultiType::create_string(substr(sval,1,-1));
}

scalar(A) ::= T_LNUMBER(sval) . { A = PC_Obj_MultiType::create_int(sval); }
scalar(A) ::= T_DNUMBER(sval) . { A = PC_Obj_MultiType::create_float(sval); }
scalar(A) ::= T_LINE . { A = PC_Obj_MultiType::create_int($this->state->get_line()); }
scalar(A) ::= T_FILE . { A = PC_Obj_MultiType::create_string($this->state->get_file()); }
scalar(A) ::= T_DIR . {
	// TODO value
	A = PC_Obj_MultiType::create_string();
}
scalar(A) ::= T_TRAIT_C|T_METHOD_C|T_FUNC_C|T_NS_C|T_CLASS_C . {
	// TODO value
	A = PC_Obj_MultiType::create_string();
}
scalar(A) ::= T_START_HEREDOC T_ENCAPSED_AND_WHITESPACE T_END_HEREDOC . {
	A = PC_Obj_MultiType::create_string();
}
scalar(A) ::= T_START_HEREDOC T_END_HEREDOC . {
	A = PC_Obj_MultiType::create_string();
}
scalar(A) ::= DOUBLEQUOTE encaps_list DOUBLEQUOTE . {
	A = PC_Obj_MultiType::create_string();
}
scalar(A) ::= T_START_HEREDOC encaps_list T_END_HEREDOC . {
	A = PC_Obj_MultiType::create_string();
}
scalar(A) ::= dereferencable_scalar(s) . { A = s; }
scalar(A) ::= constant .

constant ::= name .
constant ::= class_name T_PAAMAYIM_NEKUDOTAYIM identifier .
constant ::= variable_class_name T_PAAMAYIM_NEKUDOTAYIM identifier .

possible_comma ::= /* empty */ .
possible_comma ::= COMMA .

expr ::= variable .
expr(A) ::= expr_without_variable(e) . { A = e; }

optional_expr ::= /* empty */ .
optional_expr ::= expr .

variable_class_name ::= dereferencable .

dereferencable ::= variable .
dereferencable ::= LPAREN expr RPAREN .
dereferencable ::= dereferencable_scalar .

callable_expr ::= callable_variable .
callable_expr ::= LPAREN expr RPAREN .
callable_expr ::= dereferencable_scalar .

callable_variable ::= simple_variable .
callable_variable ::= dereferencable LBRACKET optional_expr RBRACKET .
callable_variable ::= constant LBRACKET optional_expr RBRACKET .
callable_variable ::= dereferencable LCURLY expr RCURLY .
callable_variable ::= dereferencable T_OBJECT_OPERATOR property_name argument_list .
callable_variable ::= function_call .

variable ::= callable_variable .
variable ::= static_member .
variable ::= dereferencable T_OBJECT_OPERATOR property_name .

simple_variable ::= T_VARIABLE .
simple_variable ::= DOLLAR LCURLY expr RCURLY .
simple_variable ::= DOLLAR simple_variable .

static_member ::= class_name T_PAAMAYIM_NEKUDOTAYIM simple_variable .
static_member ::= variable_class_name T_PAAMAYIM_NEKUDOTAYIM simple_variable .

new_variable ::= simple_variable .
new_variable ::= new_variable LBRACKET optional_expr RBRACKET .
new_variable ::= new_variable LCURLY expr RCURLY .
new_variable ::= new_variable T_OBJECT_OPERATOR property_name .
new_variable ::= class_name T_PAAMAYIM_NEKUDOTAYIM simple_variable .
new_variable ::= new_variable T_PAAMAYIM_NEKUDOTAYIM simple_variable .

member_name ::= identifier .
member_name ::= LCURLY expr RCURLY .
member_name ::= simple_variable .

property_name ::= T_STRING .
property_name ::= LCURLY expr RCURLY .
property_name ::= simple_variable .

assignment_list ::= assignment_list COMMA assignment_list_element .
assignment_list ::= assignment_list_element .

assignment_list_element ::= variable .
assignment_list_element ::= T_LIST LPAREN assignment_list RPAREN .
assignment_list_element ::= /* empty */ .

array_pair_list(A) ::= /* empty */ . { A = PC_Obj_MultiType::create_array(); }
array_pair_list(A) ::= non_empty_array_pair_list(list) possible_comma . { A = list; }

non_empty_array_pair_list(A) ::= non_empty_array_pair_list(list) COMMA array_pair(p) . {
	A = list;
	A->get_first()->set_array_type(p['key'],p['val'],p['append']);
}
non_empty_array_pair_list(A) ::= array_pair(p) . {
	A = PC_Obj_MultiType::create_array(array());
	A->get_first()->set_array_type(p['key'],p['val'],p['append']);
}

array_pair(A) ::= expr(skey) T_DOUBLE_ARROW expr(sval) . {
	A = array(
		'key' => skey,
		'val' => sval,
		'append' => false,
	);
}
array_pair(A) ::= expr(sval) . {
	A = array(
		'key' => 0,
		'val' => sval,
		'append' => true,
	);
}
array_pair ::= expr T_DOUBLE_ARROW AMPERSAND variable .
array_pair(A) ::= AMPERSAND variable .

encaps_list ::= encaps_list encaps_var .
encaps_list ::= encaps_list T_ENCAPSED_AND_WHITESPACE .
encaps_list ::= encaps_var .
encaps_list ::= T_ENCAPSED_AND_WHITESPACE encaps_var .

encaps_var ::= T_VARIABLE .
encaps_var ::= T_VARIABLE LBRACKET encaps_var_offset RBRACKET .
encaps_var ::= T_VARIABLE T_OBJECT_OPERATOR T_STRING .
encaps_var ::= T_DOLLAR_OPEN_CURLY_BRACES expr RCURLY .
encaps_var ::= T_DOLLAR_OPEN_CURLY_BRACES T_STRING_VARNAME RCURLY .
encaps_var ::= T_DOLLAR_OPEN_CURLY_BRACES T_STRING_VARNAME LBRACKET expr RBRACKET RCURLY .
encaps_var ::= T_CURLY_OPEN variable RCURLY .

encaps_var_offset ::= T_STRING .
encaps_var_offset ::= T_NUM_STRING .
encaps_var_offset ::= T_VARIABLE .

internal_functions_in_yacc ::= T_ISSET LPAREN isset_variables RPAREN .
internal_functions_in_yacc ::= T_EMPTY LPAREN expr RPAREN .
internal_functions_in_yacc ::= T_INCLUDE expr .
internal_functions_in_yacc ::= T_INCLUDE_ONCE expr .
internal_functions_in_yacc ::= T_EVAL LPAREN expr RPAREN .
internal_functions_in_yacc ::= T_REQUIRE expr .
internal_functions_in_yacc ::= T_REQUIRE_ONCE expr .

isset_variables ::= isset_variable .
isset_variables ::= isset_variables COMMA isset_variable .

isset_variable ::= expr .
