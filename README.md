# Användning:

### Alla FORMS använder sig utav POST.

## 1. Registerar användare.

## 2. Logga in för att få token.

## 3. Lägg till produkter i databasen.

## 4. Lek omkring!

### OBS. Man måste ha koll på sina produkters id själv!



# Projektplan:

## v1/users/addUser.php
    1. Här ska nya navändare kunna registrerar sig.
    2. användaren ska behöva fylla i fält som:
        * username
        * password
        * mail
    3. Vid registreringsförsök ska ifylld information kollas så det inte redan finns i databas.
    4. Om användarinformation inte redan existerar ska användarens information krypteras och skickas in i databas.
    5. Informationen returneras som json.

## v1/users/getUser.php
    1. Här ska registrerad användare kunna logga in.
    2. Vid inloggningsförsök ska ifylld information kollas med databasen.
    3. Lyckad inloggning blir användaren inloggad.
    4. Misslyckad inloggning får användaren felmeddelande.
    5. Inloggad användare skall sedan kunna se produkterna.
    6. Vid inloggning skapas en token för användaren.
    7. Vid inaktivitet ska användare loggas ut, det sker m.h.a "tokens".
    8. Vid aktivitet ska repsektive användares token uppdateras med ny tid för inaktivitetstid.

## v1/products/addProducts.php
    1. Användaren ska kunna lägga till products med titel, pris och beskrivning.
    2. Användaren ska kunna välja kategori för produkt.
    3. Om man lyckas lägga till produkt skall samtligt info läggas i databasen.
    4. Returneras som json.

## v1/products/getAllProducts.php
    1. Förutsatt att användaren är inloggad, ska alla products visas.
    2. Alla användare ska även kunna sortera produkter utefter vissa kategorier.

## v1/products/getProduct.php
    1. Här ska användaren kunna hämta en specifik produkt.

## v1/products/editProducts.php
    1. Formuläret fylls i av användare.
    2. Beroende på vilket id som fylls i kommer det gälla respektive produkt med samma id.
    3. Endast informationen som fylls i formuläret ska ändras.
    3. Ny information ska ersätta gammal i databasen när det skickas in.
    4. Inlägget ska nu visa ny information.

## v1/products/deleteProduct.php
    1. Om användarens token är aktiv ska produkt-id fyllas i och respektive produkt raderas.

## v1/carts/addProductsToCart.php
    1. Användare ska kunna lägga till produkter i varukorg.
    2. Användaren ska se alla produkter som ligger i varukorgen.
    3. Användare ska kunna lägga till fler eller minska antalet av samma produkt.
    4. Om användarens token går ut ska allt innehåll i varukorgen försvinna.

## v1/carts/removeProductsFromCart.php
    1. Användare ska kunna ta bort produkter ur sin varukorg.

## v1/carts/checkout.php
    1. Användare ska kunna klicka på en knapp och få bekräftelse på sina produkter från varukorgen.
     


