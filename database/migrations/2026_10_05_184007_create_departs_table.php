<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\table;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('departs', function (Blueprint $table) {
            $table->id();
            $table->string('txt_numdordrecd'); 
            $table->string('txt_avisReponsecd')->nullable();
            $table->string('txt_numdossiercd', 10)->nullable()->change(); 
            $table->index('txt_numdordrecd'); 
            $table->string('txt_typecourriercd');
            $table->string('txt_caracterecd')->nullable();
            $table->date('dt_datecouriercd');
            $table->string('txt_categoriecd')->nullable();
            $table->string('txt_referencecourierarriveecd')->nullable();
            $table->string('txt_referencecourierdepartcd')->nullable();
            $table->string('txt_nombrepiececd');
            $table->string('txt_referencecd');
            $table->string('txt_prenomNomcd')->nullable();
            $table->string('txt_numLotcd', 5)->nullable()->change();
            $table->decimal('txt_superficiecd', 10, 2)->nullable();
            $table->string('txt_numTFcd')->nullable();
            $table->string('txt_lotissementcd')->nullable();
            $table->string('txt_objetcd')->nullable();
            $table->string('txt_destinatairecd');
            $table->date('dt_dateenvoicd');
            $table->string('txt_referencereceptioncd')->nullable();
            $table->string('txt_observationcd')->nullable();
            $table->string('txt_dureetraitementcd')->nullable();
            $table->string('fichierPDFcd')->nullable();
   
            $table->timestamps(); 
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departs');
    }
};
