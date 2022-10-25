#include <stdlib.h>
#include <stdio.h>
#include <unistd.h>

void __attribute__((destructor)) vh_hack_terminate()
{
    sync();
}
