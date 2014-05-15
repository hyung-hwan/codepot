package Codepot::AuthenHandler;

use strict;
use warnings;

use Apache2::Const -compile => qw(OK DECLINED FORBIDDEN HTTP_UNAUTHORIZED  HTTP_INTERNAL_SERVER_ERROR);

sub handler: method
{
        return Apache2::Const::OK;
}
1;
